<?php

namespace App\Commands;

use App\Auth\User;
use App\models\Post;
use DateTimeImmutable;
use Symfony\Component\Yaml\Yaml;
use Tempest\Console\ConsoleCommand;
use App\Repositories\PostRepository;

use function Tempest\Database\Query;

final readonly class Cyclone
{
    private string $postsDir;

    public function __construct()
    {
        $this->postsDir = __DIR__ . '/../../content/blog';
    }

    /**
     * Copy every assets from cyclone to the final site
     *
     * @return void
     */
    #[ConsoleCommand]
    public function assets(): void
    {
        // List of files to be copied with their sources and destinations
        $filesToCopy = [
            [
                'source' => './app/Resources/img/logo.webp',
                'destination' => './public/img/logo.webp'
            ],
            [
                'source' => './app/Resources/main.entrypoint.css.stub',
                'destination' => './app/main.entrypoint.css'
            ],
            [
                'source' => './app/Resources/main.entrypoint.ts.stub',
                'destination' => './app/main.entrypoint.ts'
            ],
        ];

        // Browse each file to be copied
        foreach ($filesToCopy as $file) {
            $source = $file['source'];
            $destination = $file['destination'];
            $destinationDir = dirname($destination);

            // Check that the destination directory exists, if not create it
            if (!is_dir($destinationDir)) {
                if (!mkdir($destinationDir, 0755, true)) {
                    echo "Error: Unable to create the directory $destinationDir.\n";
                    continue; // Goes to the next file in the event of an error
                }
            }

            // Check if the source file exists
            if (file_exists($source)) {
                if (copy($source, $destination)) {
                    echo "The file " . basename($source) . " was successfully copied to $destinationDir.\n";
                } else {
                    echo "Error: Unable to copy file " . basename($source) . ".\n";
                }
            } else {
                echo "Error: The source file $source does not exist.\n";
            }
        }
    }

    #[ConsoleCommand]
    public function info(): void
    {
        echo "Cyclone v1.0.0-alpha.1\n";
    }

    #[ConsoleCommand]
    public function adduser(): void
    {
        $user = new User(
            name: 'Happy',
            email: 'happytodev@gmail.com',
        )
            ->setPassword('password')
            ->save()
            ->grantPermission('admin');
    }

    #[ConsoleCommand]
    public function addblogpost()
    {
        $repository = new PostRepository();

        // $postCounter = $repository->getTotalPosts() + 1;

        $user = User::select()
            ->where('email == ?', 'happytodev@gmail.com')
            ->first();

        for ($i = 0; $i < 30; $i++) {
            $post = Post::create(
                title: 'Lorem ipsum ' . $i,
                slug: 'lorem-ipsum-' . $i,
                tldr: 'Lorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.',
                markdown_file_path: 'lorem.md',
                user: $user,
                created_at: new DateTimeImmutable(),
                published_at: new DateTimeImmutable()
            );
        }
    }


    #[ConsoleCommand]
    public function syncPosts(): void
    {
        $files = glob($this->postsDir . '/*.md');
        $existingSlugs = [];

        foreach ($files as $file) {
            $slug = pathinfo($file, PATHINFO_FILENAME);
            $content = file_get_contents($file);
            $frontmatter = $this->parseFrontmatter($content);
            //dd($frontmatter);
            // dd($frontmatter['created_at'], DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $frontmatter['created_at']));
            $postData = [
                'slug' => $frontmatter['slug'],
                'title' => $frontmatter['title'],
                'tldr' => $frontmatter['tldr'],
                // 'image' => $frontmatter['image'],
                'markdown_file_path' => $file,
                'created_at' => DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $frontmatter['created_at']),
                'published_at' => DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $frontmatter['published_at']),
                'user_id' => 1, // check to get the current user_id
            ];

            // dd($postData);

            // Gérer la catégorie
            // $category = Category::firstOrCreate(['name' => $frontmatter['category']]);
            // $postData['category_id'] = $category->id;

            // Créer ou mettre à jour le post
            $result = Post::updateOrCreate(
                ['slug' => $postData["slug"]],
                $postData
            );

            // dd($result);

            // Gérer les tags
            // $tags = explode(',', $frontmatter['tags']);
            // $tagIds = [];
            // foreach ($tags as $tagName) {
            //     $tag = Tag::firstOrCreate(['name' => trim($tagName)]);
            //     $tagIds[] = $tag->id;
            // }
            // $post->tags()->sync($tagIds);

            $existingSlugs[] = $postData['slug'];
        }

        // Delete posts without a matching file

        // Vérifier si le tableau n'est pas vide pour éviter une erreur SQL
        if (empty($existingSlugs)) {
            // Cas particulier : supprimer tous les posts si $existingSlugs est vide
            $queryBuilder = query(Post::class)
                ->delete()
                ->allowAll(); // Permet de supprimer toutes les lignes
        } else {
            // Générer les placeholders pour le NOT IN
            $placeholders = implode(', ', array_fill(0, count($existingSlugs), '?'));
            $condition = "slug NOT IN ($placeholders)";

            // Construire la requête DELETE
            $queryBuilder = query(Post::class)
                ->delete()
                ->where($condition, ...$existingSlugs);
        }

        // Générer la requête SQL
        $query = $queryBuilder->build();

        // Afficher la requête pour débogage (facultatif)
        echo $query->getSql(); // Exemple : DELETE FROM `posts` WHERE `slug` NOT IN (?, ?, ?)
        print_r($query->bindings); // Vérifier les bindings

        // Exécuter la requête
        // Note : Remplacez "execute()" par la méthode réelle de TempestPHP si différente
        $result = $query->execute();

        if ($result) {
            echo "Posts supprimés avec succès.";
        } else {
            echo "Aucune suppression effectuée.";
        }


        echo "Posts synchronized.\n";
    }

    private function parseFrontmatter(string $content): array
    {
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $content, $matches)) {
            return Yaml::parse($matches[1]);
        }
        return [];
    }
}
