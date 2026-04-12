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
        return 'inline-flex items-center justify-center font-medium rounded-lg transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none';
    }

    public function sizeClass(): string
    {
        return match ($this->size) {
            'md' => 'px-5 py-2.5 text-sm',
            'lg' => 'px-6 py-3 text-base',
            default => 'px-3 py-1.5 text-sm',
        };
    }

    public function variantClass(): string
    {
        return match ($this->variant) {
            'primary' => 'bg-orange-500 text-white shadow-sm hover:bg-orange-600 focus:ring-orange-500 active:bg-orange-700',
            'secondary' => 'bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 focus:ring-slate-400 active:bg-slate-300',
            'danger' => '!bg-red-600 !text-white shadow-sm hover:!bg-red-500 focus:ring-red-500 active:!bg-red-700 border border-transparent',
            'outline' => 'border-2 border-orange-500 text-orange-600 bg-transparent hover:bg-orange-50 focus:ring-orange-500',
            'ghost' => 'text-slate-600 hover:bg-slate-100 focus:ring-slate-400',
            'white' => 'bg-white text-orange-600 border border-white/20 hover:bg-orange-50 focus:ring-orange-400 shadow-sm',
            'outline-light' => 'border border-white/50 text-white bg-transparent hover:bg-white/10 focus:ring-white/50',
            default => 'bg-orange-500 text-white shadow-sm hover:bg-orange-600 focus:ring-orange-500',
        };
    }

    public function render(): View|Closure|string
    {
        return view('components.btn');
    }
}
