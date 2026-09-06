import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
import test from 'node:test';

// Execute the production composer, not a second implementation of its behavior.
const template = readFileSync(new URL('../../resources/views/components/layouts/game.blade.php', import.meta.url), 'utf8');
const script = template.slice(template.indexOf('const FLW_PAD'), template.indexOf('</script>', template.indexOf('const FLW_PAD')));
function game(focused = false) {
    let factory;
    const field = { value: '\u00a0'.repeat(4), setSelectionRange() {} };
    const document = { activeElement: focused ? field : null, addEventListener: (_, callback) => callback() };
    vm.runInNewContext(script, { document, window: { FLW_WORDS: ['CARE', 'CARD', 'CORE'] }, Alpine: { data: (_, value) => { factory = value; } } });
    const editor = factory();
    editor.$refs = { field };
    editor.$wire = { submit() { throw new Error('Selection must not submit'); } };
    editor.init();
    editor.letters = ['C', 'A', 'R', 'E'];
    editor.current = 'CARE';
    editor.streak = 4;
    return editor;
}
function key(value, options = {}) {
    return { key: value, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; }, ...options };
}
for (const focused of [false, true]) {
    test(`number keys select without editing or submitting, focused=${focused}`, () => {
        const editor = game(focused);
        const handler = focused ? 'onFieldKey' : 'onKey';
        for (const digit of ['1', '2', '3', '4']) {
            const event = key(digit);
            editor[handler](event);
            assert.equal(editor.cursor, Number(digit) - 1);
            assert.equal(event.defaultPrevented, true);
            assert.equal(editor.word, 'CARE');
            assert.equal(editor.streak, 4);
        }
        editor[handler](key('3'));
        editor.type('s');
        assert.equal(editor.word, 'CASE');
    });
}
test('phone input and fallback use digits as selection commands', () => {
    const editor = game(true);
    editor.onEdit({ inputType: 'insertText', data: '3', preventDefault() {} });
    assert.equal(editor.cursor, 2);
    assert.equal(editor.word, 'CARE');
    editor.type('s');
    assert.equal(editor.word, 'CASE');
    editor.$refs.field.value = '\u00a0'.repeat(4) + '2';
    editor.onEditFallback({ target: editor.$refs.field });
    assert.equal(editor.cursor, 1);
    assert.equal(editor.$refs.field.value, '\u00a0'.repeat(4));
});
test('invalid and modified numbers leave selection alone', () => {
    for (const handler of ['onKey', 'onFieldKey']) {
        const editor = game(handler === 'onFieldKey');
        editor.cursor = 2;
        for (const event of [key('0'), key('5'), key('9'), key('1', { ctrlKey: true }), key('1', { metaKey: true }), key('1', { altKey: true })]) {
            editor[handler](event);
            assert.equal(editor.cursor, 2);
            assert.equal(event.defaultPrevented, false);
        }
    }
});
test('lost games ignore digits, and selecting from submit keeps navigation working', () => {
    const editor = game();
    editor.status = 'lost';
    editor.onKey(key('3'));
    assert.equal(editor.cursor, 0);
    editor.status = 'playing';
    editor.cursor = 'submit';
    editor.onKey(key('3'));
    editor.onKey(key('ArrowLeft'));
    assert.equal(editor.cursor, 1);
    editor.onKey(key('ArrowRight'));
    assert.equal(editor.cursor, 2);
    editor.onKey(key('Backspace'));
    assert.equal(editor.word, 'CAE');
    assert.equal(editor.cursor, 1);
});
