// Configuração dos bancos brasileiros
export interface BankConfig {
    id: string;
    name: string;
    icon: string;
    color: string;
}

export const BRAZILIAN_BANKS: BankConfig[] = [
    { id: 'bb', name: 'Banco do Brasil', icon: '/images/banks/bb.svg', color: '#FFCC00' },
    { id: 'bradesco', name: 'Bradesco', icon: '/images/banks/bradesco.svg', color: '#CC092F' },
    { id: 'caixa', name: 'Caixa Econômica Federal', icon: '/images/banks/caixa.svg', color: '#0066CC' },
    { id: 'itau', name: 'Itaú', icon: '/images/banks/itau.svg', color: '#EC7000' },
    { id: 'santander', name: 'Santander', icon: '/images/banks/santander.svg', color: '#EC0000' },
    { id: 'nubank', name: 'Nubank', icon: '/images/banks/nubank.svg', color: '#820AD1' },
    { id: 'inter', name: 'Banco Inter', icon: '/images/banks/inter.svg', color: '#FF7A00' },
    { id: 'c6', name: 'C6 Bank', icon: '/images/banks/c6.svg', color: '#1A1A1A' },
    { id: 'neon', name: 'Neon', icon: '/images/banks/neon.svg', color: '#00D4AA' },
    { id: 'sicredi', name: 'Sicredi', icon: '/images/banks/sicredi.svg', color: '#00A651' },
    { id: 'sicoob', name: 'Sicoob', icon: '/images/banks/sicoob.svg', color: '#003366' },
    { id: 'original', name: 'Banco Original', icon: '/images/banks/original.svg', color: '#00AA4F' },
    { id: 'bndes', name: 'BNDES', icon: '/images/banks/bndes.svg', color: '#0B6B3A' },
    { id: 'btg', name: 'BTG Pactual', icon: '/images/banks/btg.svg', color: '#001D3D' },
    { id: 'default', name: 'Outro Banco', icon: '/images/banks/default.svg', color: '#4B5563' },
];

export const KNOWN_BRAZILIAN_BANKS: BankConfig[] = BRAZILIAN_BANKS.filter((bank) => bank.id !== 'default');

// Configuração das bandeiras de cartões
export interface CardBrandConfig {
    id: string;
    name: string;
    icon: string;
    color: string;
}

export const CARD_BRANDS: CardBrandConfig[] = [
    { id: 'visa', name: 'Visa', icon: '/images/cards/visa.svg', color: '#1A1F71' },
    { id: 'mastercard', name: 'Mastercard', icon: '/images/cards/mastercard.svg', color: '#EB001B' },
    { id: 'elo', name: 'Elo', icon: '/images/cards/elo.svg', color: '#00A4E0' },
    { id: 'amex', name: 'American Express', icon: '/images/cards/amex.svg', color: '#006FCF' },
    { id: 'hipercard', name: 'Hipercard', icon: '/images/cards/hipercard.svg', color: '#B3131B' },
    { id: 'diners', name: 'Diners Club', icon: '/images/cards/diners.svg', color: '#004999' },
    { id: 'discover', name: 'Discover', icon: '/images/cards/default.svg', color: '#FF6000' },
    { id: 'jcb', name: 'JCB', icon: '/images/cards/default.svg', color: '#0B8F3F' },
    { id: 'aura', name: 'Aura', icon: '/images/cards/default.svg', color: '#2F5DAA' },
    { id: 'cabal', name: 'Cabal', icon: '/images/cards/default.svg', color: '#0072CE' },
    { id: 'sorocred', name: 'Sorocred', icon: '/images/cards/default.svg', color: '#F58220' },
    { id: 'default', name: 'Outra Bandeira', icon: '/images/cards/default.svg', color: '#4B5563' },
];

export const KNOWN_CARD_BRANDS: CardBrandConfig[] = CARD_BRANDS.filter((brand) => brand.id !== 'default');

// Funções auxiliares
export function normalizeBankName(value: string): string {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim()
        .toLowerCase();
}

export function getBankById(id: string): BankConfig | undefined {
    return BRAZILIAN_BANKS.find(bank => bank.id === id);
}

export function getBankByName(name: string): BankConfig | undefined {
    const normalizedName = normalizeBankName(name);
    return KNOWN_BRAZILIAN_BANKS.find(bank =>
        normalizeBankName(bank.name).includes(normalizedName) ||
        bank.id === normalizedName
    );
}

export function searchBanks(query: string): BankConfig[] {
    const normalizedQuery = normalizeBankName(query);
    if (!normalizedQuery) {
        return KNOWN_BRAZILIAN_BANKS;
    }

    return KNOWN_BRAZILIAN_BANKS.filter((bank) => {
        const normalizedBankName = normalizeBankName(bank.name);
        return normalizedBankName.includes(normalizedQuery) || bank.id.includes(normalizedQuery);
    });
}

export function getCardBrandByName(name: string): CardBrandConfig | undefined {
    const normalizedName = normalizeBankName(name);
    return KNOWN_CARD_BRANDS.find((brand) =>
        normalizeBankName(brand.name).includes(normalizedName) ||
        brand.id === normalizedName
    );
}

export function searchCardBrands(query: string): CardBrandConfig[] {
    const normalizedQuery = normalizeBankName(query);
    if (!normalizedQuery) {
        return KNOWN_CARD_BRANDS;
    }

    return KNOWN_CARD_BRANDS.filter((brand) => {
        const normalizedBrandName = normalizeBankName(brand.name);
        return normalizedBrandName.includes(normalizedQuery) || brand.id.includes(normalizedQuery);
    });
}

export function getCardBrandById(id: string): CardBrandConfig | undefined {
    return CARD_BRANDS.find(brand => brand.id === id);
}

export function getDefaultBankIcon(): string {
    return '/images/banks/default.svg';
}

export function getDefaultCardIcon(): string {
    return '/images/cards/default.svg';
}
