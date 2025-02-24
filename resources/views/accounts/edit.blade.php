<input type="text" 
       name="initial_balance" 
       id="initial_balance" 
       x-data="moneyMask" 
       x-ref="input"
       class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
       placeholder="Saldo Inicial"
       value="{{ old('initial_balance', $account->initial_balance) }}"
       required> 