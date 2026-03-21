import { BRAZILIAN_BANKS, type BankConfig } from '@/config/banks';
import { cn } from '@/lib/utils';
import { Label } from '@/components/ui/label';

interface BankIconSelectorProps {
    value: string | null;
    onChange: (value: string | null) => void;
    label?: string;
}

export function BankIconSelector({ value, onChange, label = 'Banco' }: BankIconSelectorProps) {
    return (
        <div className="space-y-2">
            {label && <Label>{label}</Label>}
            <div className="grid grid-cols-4 gap-3 rounded-2xl border border-slate-300 bg-muted/30 p-4 max-h-[240px] overflow-y-auto sm:grid-cols-5">
                {BRAZILIAN_BANKS.map((bank: BankConfig) => (
                    <button
                        key={bank.id}
                        type="button"
                        onClick={() => onChange(bank.id === value ? null : bank.id)}
                        className={cn(
                            "flex min-h-[92px] flex-col items-center justify-center rounded-xl border-2 px-2 py-3 transition-all hover:scale-[1.03]",
                            value === bank.id
                                ? "border-primary bg-primary/10"
                                : "border-transparent hover:border-muted-foreground/30"
                        )}
                        title={bank.name}
                    >
                        <img
                            src={bank.icon}
                            alt={bank.name}
                            className="h-12 w-12 object-contain sm:h-14 sm:w-14"
                        />
                        <span className="mt-2 max-w-full truncate text-[11px] font-semibold text-muted-foreground">
                            {bank.name.split(' ')[0]}
                        </span>
                    </button>
                ))}
            </div>
            {value && (
                <p className="text-sm text-muted-foreground">
                    Selecionado: {BRAZILIAN_BANKS.find(b => b.id === value)?.name}
                </p>
            )}
        </div>
    );
}
