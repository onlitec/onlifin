import { CARD_BRANDS, type CardBrandConfig } from '@/config/banks';
import { cn } from '@/lib/utils';
import { Label } from '@/components/ui/label';

interface CardBrandSelectorProps {
    value: string | null;
    onChange: (value: string | null) => void;
    label?: string;
}

export function CardBrandSelector({ value, onChange, label = 'Bandeira' }: CardBrandSelectorProps) {
    return (
        <div className="space-y-2">
            {label && <Label>{label}</Label>}
            <div className="grid grid-cols-4 gap-2 p-3 border rounded-lg bg-muted/30">
                {CARD_BRANDS.map((brand: CardBrandConfig) => (
                    <button
                        key={brand.id}
                        type="button"
                        onClick={() => onChange(brand.id === value ? null : brand.id)}
                        className={cn(
                            "flex flex-col items-center justify-center p-2 rounded-lg border-2 transition-all hover:scale-105",
                            value === brand.id
                                ? "border-primary bg-primary/10"
                                : "border-transparent hover:border-muted-foreground/30"
                        )}
                        title={brand.name}
                    >
                        <img
                            src={brand.icon}
                            alt={brand.name}
                            className="w-12 h-8 object-contain"
                        />
                        <span className="text-xs text-muted-foreground mt-1">
                            {brand.name}
                        </span>
                    </button>
                ))}
            </div>
            {value && (
                <p className="text-sm text-muted-foreground">
                    Selecionada: {CARD_BRANDS.find(b => b.id === value)?.name}
                </p>
            )}
        </div>
    );
}
