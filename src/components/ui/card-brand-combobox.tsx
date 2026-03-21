import * as React from 'react';
import { CreditCard } from 'lucide-react';
import {
  getCardBrandById,
  getCardBrandByName,
  getDefaultCardIcon,
  normalizeBankName,
  searchCardBrands,
  type CardBrandConfig,
} from '@/config/banks';
import { EntityCombobox } from '@/components/ui/entity-combobox';

interface CardBrandComboboxProps {
  brandName: string;
  iconId: string | null;
  onChange: (value: { brandName: string; iconId: string | null; brandConfig: CardBrandConfig | null }) => void;
  label?: string;
  placeholder?: string;
}

export function CardBrandCombobox({
  brandName,
  iconId,
  onChange,
  label = 'Bandeira',
  placeholder = 'Selecionar bandeira',
}: CardBrandComboboxProps) {
  const [open, setOpen] = React.useState(false);
  const [search, setSearch] = React.useState('');

  const selectedKnownBrand = React.useMemo(() => {
    return (iconId ? getCardBrandById(iconId) : null) || (brandName ? getCardBrandByName(brandName) : null) || null;
  }, [brandName, iconId]);

  const visibleBrands = React.useMemo(() => searchCardBrands(search), [search]);
  const normalizedSearch = normalizeBankName(search);
  const hasExactKnownMatch = visibleBrands.some((brand) => normalizeBankName(brand.name) === normalizedSearch);
  const showManualOption = normalizedSearch.length > 0 && !hasExactKnownMatch;

  React.useEffect(() => {
    if (!open) {
      setSearch('');
      return;
    }

    setSearch(brandName || selectedKnownBrand?.name || '');
  }, [brandName, open, selectedKnownBrand?.name]);

  const currentIcon = selectedKnownBrand?.icon || getDefaultCardIcon();
  const currentLabel = brandName || selectedKnownBrand?.name || placeholder;

  const handleKnownBrandSelect = (brand: CardBrandConfig) => {
    onChange({
      brandName: brand.name,
      iconId: brand.id,
      brandConfig: brand,
    });
    setOpen(false);
  };

  const handleManualBrandSelect = () => {
    const manualBrandName = search.trim();
    if (!manualBrandName) return;

    onChange({
      brandName: manualBrandName,
      iconId: 'default',
      brandConfig: null,
    });
    setOpen(false);
  };

  return (
    <EntityCombobox
      label={label}
      placeholder={placeholder}
      searchPlaceholder="Buscar bandeira ou digitar outra"
      manualHelperText="Salvar como bandeira digitada manualmente"
      valueLabel={currentLabel === placeholder ? '' : currentLabel}
      valueIcon={currentIcon}
      selectedId={selectedKnownBrand?.id || null}
      options={visibleBrands}
      searchValue={search}
      setSearchValue={setSearch}
      open={open}
      setOpen={setOpen}
      showManualOption={showManualOption}
      emptyStateIcon={<CreditCard className="h-4 w-4 text-slate-400" />}
      onKnownSelect={handleKnownBrandSelect}
      onManualSelect={handleManualBrandSelect}
    />
  );
}
