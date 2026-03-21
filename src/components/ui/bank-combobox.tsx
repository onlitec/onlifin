import * as React from 'react';
import { Landmark } from 'lucide-react';
import {
  getBankById,
  getBankByName,
  getDefaultBankIcon,
  normalizeBankName,
  searchBanks,
  type BankConfig,
} from '@/config/banks';
import { EntityCombobox } from '@/components/ui/entity-combobox';

interface BankComboboxProps {
  bankName: string;
  iconId: string | null;
  onChange: (value: { bankName: string; iconId: string | null; bankConfig: BankConfig | null }) => void;
  label?: string;
  placeholder?: string;
}

export function BankCombobox({
  bankName,
  iconId,
  onChange,
  label = 'Banco',
  placeholder = 'Selecionar banco',
}: BankComboboxProps) {
  const [open, setOpen] = React.useState(false);
  const [search, setSearch] = React.useState('');

  const selectedKnownBank = React.useMemo(() => {
    return (iconId ? getBankById(iconId) : null) || (bankName ? getBankByName(bankName) : null) || null;
  }, [bankName, iconId]);

  const visibleBanks = React.useMemo(() => searchBanks(search), [search]);
  const normalizedSearch = normalizeBankName(search);
  const hasExactKnownMatch = visibleBanks.some((bank) => normalizeBankName(bank.name) === normalizedSearch);
  const showManualOption = normalizedSearch.length > 0 && !hasExactKnownMatch;

  React.useEffect(() => {
    if (!open) {
      setSearch('');
      return;
    }

    setSearch(bankName || selectedKnownBank?.name || '');
  }, [bankName, open, selectedKnownBank?.name]);

  const currentIcon = selectedKnownBank?.icon || getDefaultBankIcon();
  const currentLabel = bankName || selectedKnownBank?.name || placeholder;

  const handleKnownBankSelect = (bank: BankConfig) => {
    onChange({
      bankName: bank.name,
      iconId: bank.id,
      bankConfig: bank,
    });
    setOpen(false);
  };

  const handleManualBankSelect = () => {
    const manualBankName = search.trim();
    if (!manualBankName) return;

    onChange({
      bankName: manualBankName,
      iconId: 'default',
      bankConfig: null,
    });
    setOpen(false);
  };

  return (
    <EntityCombobox
      label={label}
      placeholder={placeholder}
      searchPlaceholder="Buscar banco brasileiro ou digitar outro banco"
      manualHelperText="Salvar como banco digitado manualmente"
      valueLabel={currentLabel === placeholder ? '' : currentLabel}
      valueIcon={currentIcon}
      selectedId={selectedKnownBank?.id || null}
      options={visibleBanks}
      searchValue={search}
      setSearchValue={setSearch}
      open={open}
      setOpen={setOpen}
      showManualOption={showManualOption}
      emptyStateIcon={<Landmark className="h-4 w-4 text-slate-400" />}
      onKnownSelect={handleKnownBankSelect}
      onManualSelect={handleManualBankSelect}
    />
  );
}
