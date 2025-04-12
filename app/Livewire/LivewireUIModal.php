<?php

namespace App\Livewire;

use Livewire\Component;

class LivewireUIModal extends Component
{
    public $modalId = null;
    public $modalComponent = null;
    public $modalAttributes = [];
    
    protected $listeners = [
        'openModal' => 'openModal',
        'closeModal' => 'closeModal',
    ];
    
    public function openModal($component, $attributes = [])
    {
        $this->reset(['modalId', 'modalComponent', 'modalAttributes']);
        
        $this->modalId = md5($component . serialize($attributes));
        $this->modalComponent = $component;
        $this->modalAttributes = $attributes;
        
        // Debug para verificar se está recebendo o evento
        // Loga no arquivo de log do Laravel
        \Illuminate\Support\Facades\Log::info('Modal aberto: ' . $component);
    }
    
    public function closeModal()
    {
        $this->reset(['modalId', 'modalComponent', 'modalAttributes']);
    }
    
    public function render()
    {
        return view('livewire.livewire-u-i-modal');
    }
}
