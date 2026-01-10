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
            <div className="grid grid-cols-5 gap-2 p-3 border rounded-lg bg-muted/30 max-h-[200px] overflow-y-auto">
                {BRAZILIAN_BANKS.map((bank: BankConfig) => (
                    <button
                        key={bank.id}
                        type="button"
                        onClick={() => onChange(bank.id === value ? null : bank.id)}
                        className={cn(
                            "flex flex-col items-center justify-center p-2 rounded-lg border-2 transition-all hover:scale-105",
                            value === bank.id
                                ? "border-primary bg-primary/10"
                                : "border-transparent hover:border-muted-foreground/30"
                        )}
                        title={bank.name}
                    >
                        <img
                            src={bank.icon}
                            alt={bank.name}
                            className="w-10 h-10 object-contain"
                        />
                        <span className="text-xs text-muted-foreground mt-1 truncate max-w-full">
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
