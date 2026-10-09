{{-- The template body is admin-authored HTML; shortcode values (names, links…) are escaped. --}}
{!! str_replace(array_keys($short_codes), array_map('e', array_values($short_codes)), $body) !!}
