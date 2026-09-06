<div>
    <div class="lpg" wire:ignore x-data="lpGame()">
        <div class="wr-rainbow" aria-hidden="true"></div>

        <div class="lpg__wrap">
            <header class="lpg__head">
                <span class="lpg__mark">LICENSE·PLATE·GAME</span>
                <a href="{{ route('home') }}" class="lpg__back">← mary.win</a>
            </header>

            {{-- settings: state pick XOR letter preference (R6), rob's rule, timer --}}
            <section class="lpg__settings">
                <label>
                    state
                    <select x-model="stateCode" @change="pickState()">
                        <option value="">🎲 random</option>
                        @foreach (\App\Games\LicensePlates\StateFormatList::raw() as $format)
                            <option value="{{ $format['code'] }}">{{ strtolower($format['name']) }}</option>
                        @endforeach
                    </select>
                </label>

                <template x-if="!stateCode">
                    <span style="display:inline-flex; gap:1rem;">
                        <label><input type="checkbox" x-model="exclTwo" @change="pickPrefs()"> no 2-letter plates</label>
                        <label><input type="checkbox" x-model="exclMore" @change="pickPrefs()"> no 3+-letter plates</label>
                    </span>
                </template>

                <label :style="plate && !robsAvailable ? 'opacity:.45' : ''">
                    <input type="checkbox" x-model="robs" @change="pickRobs()" :disabled="plate && !robsAvailable">
                    rob's rule
                    <span class="lpg__hint" x-show="plate && !robsAvailable">(needs 3+ letters)</span>
                </label>

                <label>
                    <input type="checkbox" x-model="timerOn" @change="pickTimer()"> timer
                    <input type="number" min="1" x-model="timerSecs" @change="timerOn && pickTimer()" :disabled="!timerOn"> s
                </label>

                <button type="button" class="lpg__btn" @click="newPlate()">new plate</button>
            </section>

            {{-- IDLE --}}
            <main class="lpg__stage" x-show="status === 'idle'">
                <p class="lpg__prompt">press <b>NEW PLATE</b> — then make words out of whatever plate you see.</p>
                <p class="lpg__note">the plate's letters must appear in your word, in order. numbers are decoration; the passenger is always right.</p>
            </main>

            {{-- PLAYING --}}
            <main class="lpg__stage" x-show="status === 'playing'" x-cloak>
                <div class="lpg__plate" :style="plateStyle()">
                    <span class="lpg__plate-state" x-text="plate?.name"></span>
                    <div class="lpg__plate-chars">
                        <template x-for="(c, i) in (plate?.cells || [])" :key="i">
                            <span class="lpg__cell" :class="c.t === 'N' && 'lpg__cell--num'" x-text="c.ch"></span>
                        </template>
                    </div>
                </div>

                <p class="lpg__prompt">
                    build a word around <b x-text="(plate?.letters || '').split('').join(' · ')"></b> — in that order.
                </p>

                <form class="lpg__entry" @submit.prevent="play()">
                    <input type="text" x-model="draft" x-ref="word" placeholder="your word" autocomplete="off" autocapitalize="none">
                    <button type="submit" class="lpg__btn">play</button>
                    <button type="button" class="lpg__btn lpg__btn--ghost" @click="endRound()">finish</button>
                </form>

                <p class="lpg__message" :class="messageKind === 'err' && 'err'" x-text="message"></p>

                <div class="lpg__meta">
                    <span>SCORE <b x-text="score"></b></span>
                    <span>WORDS <b x-text="played.length"></b></span>
                    <span class="lpg__timer" :class="remaining !== null && remaining <= 5 && 'low'" x-show="remaining !== null">
                        TIME <b x-text="remaining + 's'"></b>
                    </span>
                </div>

                <ul class="lpg__chips">
                    <template x-for="p in played" :key="p.word">
                        <li><span x-text="p.word"></span><b x-text="'+' + p.points"></b></li>
                    </template>
                </ul>
            </main>

            {{-- OVER --}}
            <main class="lpg__stage" x-show="status === 'over'" x-cloak>
                <p class="lpg__over-title">round over.</p>
                <div class="lpg__meta"><span>SCORE <b x-text="result?.score ?? 0"></b></span></div>

                <ul class="lpg__chips">
                    <template x-for="w in (result?.words || [])" :key="w">
                        <li x-text="w"></li>
                    </template>
                </ul>
                <p class="lpg__note" x-show="!(result?.words || []).length">no words this round — the plate won.</p>

                <button type="button" class="lpg__btn" @click="newPlate()">play again →</button>
            </main>
        </div>
    </div>
</div>
