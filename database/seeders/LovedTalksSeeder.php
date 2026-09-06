<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\ResearchSource;
use Illuminate\Database\Seeder;

/**
 * The talks-worth-watching list, lifted from the clever/landing research
 * library: every `type: talk` source in the Cleverness-is-a-Debt bibliography,
 * minus the Laravel-origins evidence sources (keynotes, an interview and a
 * documentary cited as evidence rather than recommended).
 *
 * Idempotent: sources upsert on the synthetic `vault_path` clever uses for
 * bibliography-only rows, people upsert on slug.
 */
class LovedTalksSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/loved-talks.json');

        if (! is_file($path)) {
            $this->command?->warn("loved-talks.json missing at {$path} — skipping.");

            return;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            $this->command?->warn('loved-talks.json is not valid JSON — skipping.');

            return;
        }

        /** @var array<string, Person> $people */
        $people = [];

        foreach ($data['people'] ?? [] as $person) {
            if (empty($person['slug']) || empty($person['name'])) {
                continue;
            }

            $people[$person['slug']] = Person::updateOrCreate(
                ['slug' => $person['slug']],
                [
                    'name' => $person['name'],
                    'kind' => $person['kind'] ?? Person::KIND_PERSON,
                    'url' => $person['url'] ?? null,
                ],
            );
        }

        $count = 0;

        foreach ($data['sources'] ?? [] as $source) {
            if (empty($source['slug']) || empty($source['title'])) {
                continue;
            }

            $authorSlugs = is_array($source['authors'] ?? null) ? $source['authors'] : [];
            $authorNames = array_values(array_filter(array_map(
                fn (string $slug): ?string => $people[$slug]->name ?? null,
                $authorSlugs,
            )));

            $record = ResearchSource::updateOrCreate(
                ['vault_path' => "clever/bib/{$source['slug']}"],
                [
                    'title' => $source['title'],
                    'type' => ResearchSource::TYPE_TALK,
                    'is_visible' => true,
                    'url' => $source['url'] ?? null,
                    'date_published' => $this->normalizeDate($source['date_published'] ?? null),
                    'artifact_status' => $source['artifact_status'] ?? ResearchSource::STATUS_UNKNOWN,
                    'summary' => $source['summary'] ?? null,
                    // Denormalized display cache; the graph rides the pivot.
                    'author' => $authorNames !== [] ? implode(' & ', $authorNames) : null,
                ],
            );

            foreach ($authorSlugs as $position => $slug) {
                if (isset($people[$slug])) {
                    $record->people()->syncWithoutDetaching([
                        $people[$slug]->id => ['role' => 'author', 'position' => $position],
                    ]);
                }
            }

            $count++;
        }

        $this->command?->info("Seeded {$count} talks from the research library.");
    }

    /**
     * Bibliography dates arrive as full dates, months, bare years or "~2011".
     * Same normalization clever uses so the two libraries agree.
     */
    private function normalizeDate(?string $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        if (preg_match('/^\d{4}-\d{2}$/', $date)) {
            return $date.'-01';
        }

        if (preg_match('/(\d{4})/', $date, $matches)) {
            return $matches[1].'-01-01';
        }

        return null;
    }
}
