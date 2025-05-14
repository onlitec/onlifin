@php
    use App\Models\ReplicateSetting;
    
    // Busca as configurações ativas da IA
    $aiSettings = ReplicateSetting::getActive();
    
    // Define ícones para cada provedor
    $providerIcons = [
        'openai' => 'ri-openai-fill',
        'anthropic' => 'ri-robot-line',
        'gemini' => 'ri-google-fill',
        'grok' => 'ri-twitter-x-fill',
        'copilot' => 'ri-github-fill',
        'tongyi' => 'ri-alipay-line',
        'deepseek' => 'ri-brain-line',
        'openrouter' => 'ri-global-fill'
    ];
    
    // Define cores para cada provedor
    $providerColors = [
        'openai' => 'bg-green-100 text-green-800',
        'anthropic' => 'bg-purple-100 text-purple-800',
        'gemini' => 'bg-blue-100 text-blue-800',
        'grok' => 'bg-black text-white',
        'copilot' => 'bg-gray-100 text-gray-800',
        'tongyi' => 'bg-orange-100 text-orange-800',
        'deepseek' => 'bg-indigo-100 text-indigo-800',
        'openrouter' => 'bg-blue-100 text-blue-800'
    ];
    
    // Define nomes amigáveis para cada provedor
    $providerNames = [
        'openai' => 'OpenAI',
        'anthropic' => 'Anthropic',
        'gemini' => 'Google Gemini',
        'grok' => 'Grok',
        'copilot' => 'GitHub Copilot',
        'tongyi' => 'Tongyi (Qwen)',
        'deepseek' => 'Deepseek',
        'openrouter' => 'OpenRouter'
    ];
@endphp

@if($aiSettings && $aiSettings->is_active)
    <div class="ai-status-component">
        <div class="flex items-center gap-2">
            <div class="rounded-full p-1 {{ $providerColors[$aiSettings->provider] ?? 'bg-gray-100 text-gray-800' }} flex items-center justify-center">
                <i class="{{ $providerIcons[$aiSettings->provider] ?? 'ri-ai-generate' }} text-sm"></i>
            </div>
            <div class="text-xs font-medium">
                <span>IA: {{ $providerNames[$aiSettings->provider] ?? 'Desconhecido' }}</span>
                <span class="text-gray-500 ml-1">{{ $aiSettings->model_version }}</span>
            </div>
            <a href="{{ route('openrouter-config.index') }}" class="text-xs text-blue-600 hover:text-blue-800">
                <i class="ri-settings-3-line"></i>
            </a>
        </div>
    </div>
@else
    <div class="ai-status-component">
        <div class="flex items-center gap-2">
            <div class="rounded-full p-1 bg-gray-100 text-gray-800 flex items-center justify-center">
                <i class="ri-ai-generate text-sm"></i>
            </div>
            <div class="text-xs font-medium text-gray-500">
                IA não configurada
            </div>
            <a href="{{ route('openrouter-config.index') }}" class="text-xs text-blue-600 hover:text-blue-800">
                <i class="ri-settings-3-line"></i>
            </a>
        </div>
    </div>
@endif
