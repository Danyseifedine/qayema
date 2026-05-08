<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Btn extends Component
{
    public string $buttonClass;

    public function __construct(
        public string $variant = 'primary',
        public string $size = 'sm',
        public ?string $href = null,
        public string $type = 'button',
    ) {
        $this->buttonClass = $this->baseClass().' '.$this->sizeClass().' '.$this->variantClass();
    }

    public function baseClass(): string
    {
        return 'ui-btn';
    }

    public function sizeClass(): string
    {
        return '';
    }

    public function variantClass(): string
    {
        return match ($this->variant) {
            'secondary', 'white', 'outline-light' => 'ui-btn-secondary',
            'danger' => 'ui-btn-danger',
            'ghost', 'outline' => 'ui-btn-ghost',
            default => 'ui-btn-primary',
        };
    }

    public function render(): View|Closure|string
    {
        return view('components.btn');
    }
}
