<div>
    <noscript><p><a href="{{ route('games.flw.play') }}">Play Four Letter Words without JavaScript</a></p></noscript>
    <div class="flw" wire:ignore x-data="flwComposer()" x-init="init()" @keydown.window="onKey($event)">
        <div class="wr-rainbow" aria-hidden="true"></div>

        <div class="flw__wrap">
            {{-- The real input, and the whole reason a phone raises its keyboard here: taps on
                 a button never do. Invisible and click-through, so the boxes below stay the
                 visible field. It lives outside the stages on purpose — x-show is applied a
                 tick late, and a hidden element cannot take the focus that "play again" hands
                 it while still inside the tap. --}}
            <input class="flw__typing" type="text" x-ref="field"
                   aria-label="type a four letter word"
                   aria-describedby="flw-keyboard-help"
                   autocomplete="off" autocorrect="off" autocapitalize="characters"
                   spellcheck="false" inputmode="text" enterkeyhint="go"
                   @beforeinput="onEdit($event)"
                   @input="onEditFallback($event)"
                   @keydown="onFieldKey($event)">

            <p id="flw-keyboard-help" hidden>Press 1, 2, 3, or 4 to select a box from the left. Type a letter to replace the selected box.</p>

            <header class="flw__head">
                <span class="flw__mark">FOUR·LETTER·WORDS</span>
                <a href="{{ route('games.flw.play') }}">Start a game with account saves</a>
                <a href="{{ route('home') }}" class="flw__back">← mary.win</a>
            </header>

            {{-- PLAYING --}}
            {{-- A tap anywhere on the stage raises the keyboard; @mousedown.prevent keeps the
                 buttons from stealing focus off the field and dropping it again. --}}
            <main class="flw__stage" x-show="status === 'playing'" @click="focusField()">
                <p class="flw__prompt" x-text="promptText()"></p>

                <div class="flw__streak" x-show="streak > 0">STREAK&nbsp; <b x-text="streak"></b></div>

                <div class="flw__boxes">
                    <template x-for="(l, i) in letters" :key="i">
                        <button type="button" class="flw__box" tabindex="-1"
                                :class="{ 'is-selected': cursor === i }"
                                :aria-label="'letter ' + (i + 1) + ', ' + (l || 'empty') + (cursor === i ? ', selected' : '')"
                                :aria-current="cursor === i ? 'true' : null"
                                @mousedown.prevent
                                @click="select(i)" x-text="l"></button>
                    </template>
                </div>

                <button type="button" class="flw__submit" x-show="armed" x-cloak
                        :class="{ 'is-focused': cursor === 'submit' }"
                        @mousedown.prevent
                        @click="trySubmit()">SELECT →</button>
            </main>

            {{-- LOST --}}
            <main class="flw__stage" x-show="status === 'lost'" x-cloak>
                <p class="flw__over-title">you lost.</p>
                <p class="flw__over-sub">STREAK&nbsp; <b x-text="finalStreak"></b></p>

                <ol class="flw__log">
                    <template x-for="w in log" :key="w"><li x-text="w"></li></template>
                </ol>

                <p class="flw__note"><a href="{{ route('games.flw.play') }}">Start a game with account saves</a></p>

                <button type="button" class="flw__again" @mousedown.prevent @click="playAgain()">play again →</button>
            </main>
        </div>
    </div>
</div>
