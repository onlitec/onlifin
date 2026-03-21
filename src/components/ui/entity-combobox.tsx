import * as React from 'react';
import { Check, ChevronsUpDown } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

interface EntityOption {
  id: string;
  name: string;
  icon: string;
}

interface EntityComboboxProps<TOption extends EntityOption> {
  label: string;
  placeholder: string;
  searchPlaceholder: string;
  manualHelperText: string;
  valueLabel: string;
  valueIcon: string;
  selectedId: string | null;
  options: TOption[];
  searchValue: string;
  setSearchValue: (value: string) => void;
  open: boolean;
  setOpen: (value: boolean) => void;
  showManualOption: boolean;
  emptyStateIcon: React.ReactNode;
  onKnownSelect: (option: TOption) => void;
  onManualSelect: () => void;
}

export function EntityCombobox<TOption extends EntityOption>({
  label,
  placeholder,
  searchPlaceholder,
  manualHelperText,
  valueLabel,
  valueIcon,
  selectedId,
  options,
  searchValue,
  setSearchValue,
  open,
  setOpen,
  showManualOption,
  emptyStateIcon,
  onKnownSelect,
  onManualSelect,
}: EntityComboboxProps<TOption>) {
  return (
    <div className="space-y-2">
      <Label className="ml-1 text-[10px] font-black uppercase tracking-widest opacity-50">{label}</Label>
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button
            type="button"
            variant="outline"
            role="combobox"
            aria-expanded={open}
            className="glass-card h-12 w-full justify-between rounded-xl border-slate-300 px-3 font-medium"
          >
            <span className="flex min-w-0 items-center gap-3">
              <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-50">
                {valueLabel ? (
                  <img src={valueIcon} alt={valueLabel} className="h-6 w-6 object-contain" />
                ) : (
                  emptyStateIcon
                )}
              </span>
              <span className={cn('truncate', !valueLabel && 'text-slate-400')}>
                {valueLabel || placeholder}
              </span>
            </span>
            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
          </Button>
        </PopoverTrigger>
        <PopoverContent align="start" className="w-[420px] max-w-[calc(100vw-2rem)] rounded-2xl border-slate-200 p-0">
          <Command shouldFilter={false}>
            <CommandInput
              placeholder={searchPlaceholder}
              value={searchValue}
              onValueChange={setSearchValue}
            />
            <CommandList className="max-h-[320px]">
              <CommandEmpty>Nenhum item encontrado.</CommandEmpty>
              {options.map((option) => {
                const isSelected = selectedId === option.id;
                return (
                  <CommandItem
                    key={option.id}
                    value={option.id}
                    onSelect={() => onKnownSelect(option)}
                    className="gap-3 px-3 py-3"
                  >
                    <img src={option.icon} alt={option.name} className="h-8 w-8 rounded-md object-contain" />
                    <div className="min-w-0 flex-1">
                      <p className="truncate font-semibold">{option.name}</p>
                    </div>
                    <Check className={cn('h-4 w-4', isSelected ? 'opacity-100' : 'opacity-0')} />
                  </CommandItem>
                );
              })}
              {showManualOption && (
                <CommandItem
                  value={`manual:${searchValue}`}
                  onSelect={onManualSelect}
                  className="gap-3 border-t border-slate-100 px-3 py-3"
                >
                  <img src={valueIcon} alt="Opção manual" className="h-8 w-8 rounded-md object-contain" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-semibold">Usar "{searchValue.trim()}"</p>
                    <p className="text-xs text-muted-foreground">{manualHelperText}</p>
                  </div>
                </CommandItem>
              )}
            </CommandList>
          </Command>
        </PopoverContent>
      </Popover>
    </div>
  );
}
