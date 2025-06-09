<?php

namespace App\Controllers;

use App\models\Post;
use Tempest\View\View;
use Tempest\Router\Get;
use Tempest\Http\Request;
use App\Views\Post\PostsListView;
use App\Repositories\PostRepository;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Environment\Environment;
use Tempest\Highlight\CommonMark\HighlightExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;

use function Tempest\view;

final class BlogController
{
    public function __construct(
        private PostRepository $repository,
    ) {}

    #[Get('/blog')]
    public function __invoke(Request $request): View
    {
        // Number of posts per page
        $perPage = 9;

        // Current page, minimum 1
        $page = max(1, (int)$request->get('page', 1));

        // Calculate offset for pagination
        $offset = ($page - 1) * $perPage;

        // Get paginated posts
        $posts = $this->repository->getPosts($perPage, $offset);

        // Get total number of posts
        $totalPosts = $this->repository->getTotalPosts();

        // Calculate total number of pages
        $totalPages = (int)ceil($totalPosts / $perPage);

        // Pass data to the view
        return new PostsListView($posts, $page, $totalPages);
    }

    #[Get(uri: '/blog/{slug}')]
    public function show(Post $slug): View
    {
        $post = $slug;

        $rootPath = realpath(getcwd() . '/../');
        $markdownPath = realpath($rootPath . DIRECTORY_SEPARATOR . $post->markdown_file_path);

        $environment = new Environment();
        $environment
            ->addExtension(new CommonMarkCoreExtension())
            ->addExtension(new HighlightExtension());

        if (file_exists($markdownPath)) {
            $markdownContent = file_get_contents($markdownPath);

            // Extract frontmatter and Markdown body to avoid displaying metadata
            $parts = $this->extractFrontmatter($markdownContent);
            $markdownBody = $parts['body'];

            // Convert only the Markdown body to HTML
            $converter = new MarkdownConverter($environment);
            $content = $converter->convert($markdownBody);
        } else {
            $content = 'Content not found';
        }

        return view('../Views/Post/show.view.php', post: $post, content: $content);
    }

    /**
     * Extracts frontmatter from a Markdown file and returns the remaining content.
     *
     * @param string $content Raw Markdown file content
     * @return array Array with 'frontmatter' (string) and 'body' (string)
     */
    private function extractFrontmatter(string $content): array
    {
        $frontmatter = '';
        $body = $content;

        // Check if content starts with frontmatter delimiters (---)
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches)) {
            $frontmatter = $matches[1]; // Metadata between delimiters
            $body = $matches[2]; // Remaining Markdown content
        }

        return [
            'frontmatter' => $frontmatter,
            'body' => $body,
        ];
    }
}
