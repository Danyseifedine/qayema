{{-- Long-form text from lang/{locale}/pages.php: each section a heading,
     paragraphs, an optional list of points and paragraphs after it. --}}
<article class="prose">
  @foreach ($sections as $section)
    <h2>{{ $section['title'] }}</h2>
    @foreach ($section['body'] ?? [] as $paragraph)
      <p>{{ $paragraph }}</p>
    @endforeach
    @if (! empty($section['points']))
      <ul>
        @foreach ($section['points'] as $point)
          <li>{{ $point }}</li>
        @endforeach
      </ul>
    @endif
    @foreach ($section['after'] ?? [] as $paragraph)
      <p>{{ $paragraph }}</p>
    @endforeach
  @endforeach
</article>
