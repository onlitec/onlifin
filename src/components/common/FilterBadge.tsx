import { X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface FilterBadgeProps {
  label: string;
  value: string;
  onRemove: () => void;
}

export function FilterBadge({ label, value, onRemove }: FilterBadgeProps) {
  return (
    <Badge variant="secondary" className="gap-2 px-3 py-1.5 text-sm">
      <span className="font-medium">{label}:</span>
      <span>{value}</span>
      <Button
        variant="ghost"
        size="sm"
        className="h-4 w-4 p-0 hover:bg-transparent"
        onClick={onRemove}
      >
        <X className="h-3 w-3" />
      </Button>
    </Badge>
  );
}

interface ActiveFiltersBarProps {
  filters: Array<{
    key: string;
    label: string;
    value: string;
    onRemove: () => void;
  }>;
  onClearAll: () => void;
}

export function ActiveFiltersBar({ filters, onClearAll }: ActiveFiltersBarProps) {
  if (filters.length === 0) return null;

  return (
    <div className="flex flex-wrap items-center gap-2 p-4 bg-muted/50 rounded-lg border border-border">
      <span className="text-sm font-medium text-muted-foreground">Filtros ativos:</span>
      {filters.map((filter) => (
        <FilterBadge
          key={filter.key}
          label={filter.label}
          value={filter.value}
          onRemove={filter.onRemove}
        />
      ))}
      <Button
        variant="ghost"
        size="sm"
        onClick={onClearAll}
        className="ml-auto text-xs"
      >
        Limpar todos
      </Button>
    </div>
  );
}
