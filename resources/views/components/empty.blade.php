@props(['text'=>null])<div class="empty-state"><i data-lucide="inbox"></i><p>{{ $text ?? __('ui.empty') }}</p>{{ $slot }}</div>
