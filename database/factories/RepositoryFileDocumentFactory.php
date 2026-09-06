<?php

namespace Database\Factories;

use App\Models\Repository;
use App\Models\RepositoryFileDocument;
use App\Models\RepositoryFiles;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepositoryFileDocument>
 */
class RepositoryFileDocumentFactory extends Factory
{
    protected $model = RepositoryFileDocument::class;

    public function definition(): array
    {
        return [
            'repository_file_id' => RepositoryFiles::factory(),
            'repository_id' => Repository::factory(),
            'content' => "<?php\n\nfunction greet(\$name) {\n    return strtoupper(\$name);\n}\n",
        ];
    }
}
