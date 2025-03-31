<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <div class="max-w-xl">
        <section>
            <header>
                <h2 class="text-lg font-medium text-gray-900">
                    {{ __('Configurações de Notificações') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Configure como você deseja receber notificações do sistema.') }}
                </p>
            </header>

            <form wire:submit="save" class="mt-6 space-y-6">
                <!-- Configurações de Email -->
                <div class="border-b pb-6">
                    <h3 class="text-md font-medium text-gray-900 mb-4">{{ __('Notificações por Email') }}</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input wire:model="email_notifications_enabled" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            </div>
                            <div class="ml-3 text-sm">
                                <label class="font-medium text-gray-700">{{ __('Habilitar notificações por email') }}</label>
                                <p class="text-gray-500">{{ __('Receba atualizações importantes por email.') }}</p>
                            </div>
                        </div>

                        <div class="space-y-4 ml-8" x-show="$wire.email_notifications_enabled">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input wire:model="email_notify_new_transactions" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label class="font-medium text-gray-700">{{ __('Novas transações') }}</label>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input wire:model="email_notify_due_dates" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label class="font-medium text-gray-700">{{ __('Datas de vencimento') }}</label>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input wire:model="email_notify_low_balance" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label class="font-medium text-gray-700">{{ __('Saldo baixo') }}</label>
                                </div>
                            </div>

                            <div class="ml-8" x-show="$wire.email_notify_low_balance">
                                <label class="block text-sm font-medium text-gray-700">{{ __('Limite de saldo baixo') }}</label>
                                <div class="mt-1">
                                    <input wire:model="email_low_balance_threshold" type="number" step="0.01" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configurações de WhatsApp -->
                <div class="pt-6">
                    <h3 class="text-md font-medium text-gray-900 mb-4">{{ __('Notificações por WhatsApp') }}</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input wire:model="whatsapp_notifications_enabled" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            </div>
                            <div class="ml-3 text-sm">
                                <label class="font-medium text-gray-700">{{ __('Habilitar notificações por WhatsApp') }}</label>
                                <p class="text-gray-500">{{ __('Receba atualizações importantes por WhatsApp.') }}</p>
                            </div>
                        </div>

                        <div class="space-y-4 ml-8" x-show="$wire.whatsapp_notifications_enabled">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Número do WhatsApp') }}</label>
                                <div class="mt-1">
                                    <input wire:model="whatsapp_number" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Ex: +5511999999999">
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input wire:model="whatsapp_notify_new_transactions" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label class="font-medium text-gray-700">{{ __('Novas transações') }}</label>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input wire:model="whatsapp_notify_due_dates" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label class="font-medium text-gray-700">{{ __('Datas de vencimento') }}</label>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input wire:model="whatsapp_notify_low_balance" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label class="font-medium text-gray-700">{{ __('Saldo baixo') }}</label>
                                </div>
                            </div>

                            <div class="ml-8" x-show="$wire.whatsapp_notify_low_balance">
                                <label class="block text-sm font-medium text-gray-700">{{ __('Limite de saldo baixo') }}</label>
                                <div class="mt-1">
                                    <input wire:model="whatsapp_low_balance_threshold" type="number" step="0.01" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Salvar') }}
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
