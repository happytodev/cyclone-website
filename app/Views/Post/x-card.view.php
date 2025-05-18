<article class="w-full bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
    <!-- Main image -->
    <div class="h-48 w-full overflow-hidden">
        <img
            src="{{ 'https://picsum.photos/200/400' }}"
            alt="{{ $post->title }}"
            class="w-full h-full object-cover">
    </div>

    <!-- Card content -->
    <div class="p-6">
        <!-- Category -->
        <!-- <span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded mb-2">
                            {{ $post->category->name ?? 'Uncategorized' }}
                        </span> -->

        <!-- Title -->
        <h2 class="text-xl font-semibold text-gray-900 mb-2">
            <a href="/blog/{{ $post->slug }}" class="hover:text-sky-600">
                {{ $post->title }}
            </a>
        </h2>

        <!-- TLDR -->
        <p class="text-gray-600 text-sm mb-4 line-clamp-3">
            {{ $post->tldr }}
        </p>

        <!-- Metadatas -->
        <div class="flex items-center text-sm text-gray-500">
            <div class="mt-4 text-sm text-gray-500">
                <span> by {{ $post->user->name }}</span>
                <span :if="$post->published_at"> on {{ $post->published_at->format('M j, Y') }}</span>
            </div>
        </div>
    </div>
</article>