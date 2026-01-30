/**
 * Dialog para criação e edição de empresa
 */

import * as React from 'react';
import { useState, useEffect, useCallback } from 'react';
import { Loader2, Search, Building2, MapPin, Phone, Mail, Globe, CreditCard } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useToast } from '@/hooks/use-toast';
import { cn } from '@/lib/utils';
import { validateCNPJ, formatCNPJ, formatCEP, formatPhone, validateCEP } from '@/utils/validators';
import { consultarCNPJ, consultarCEP } from '@/utils/cnpjApi';
import type { Company, CreateCompanyDTO, UpdateCompanyDTO, CompanySize, TaxRegime } from '@/types/company';
import { COMPANY_SIZE_LABELS, TAX_REGIME_LABELS, UF_OPTIONS } from '@/types/company';

interface CompanyDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    company?: Company | null;
    onSave: (data: CreateCompanyDTO | UpdateCompanyDTO) => Promise<void>;
}

const EMPTY_FORM: CreateCompanyDTO = {
    cnpj: '',
    razao_social: '',
    nome_fantasia: '',
    inscricao_estadual: '',
    inscricao_municipal: '',
    cep: '',
    logradouro: '',
    numero: '',
    complemento: '',
    bairro: '',
    cidade: '',
    uf: '',
    email: '',
    phone: '',
    website: '',
    porte: undefined,
    regime_tributario: undefined,
    banco_padrao: '',
    agencia_padrao: '',
    conta_padrao: '',
};

export function CompanyDialog({
    open,
    onOpenChange,
    company,
    onSave,
}: CompanyDialogProps) {
    const { toast } = useToast();
    const isEdit = !!company;

    const [formData, setFormData] = useState<CreateCompanyDTO>(EMPTY_FORM);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isLoadingCNPJ, setIsLoadingCNPJ] = useState(false);
    const [isLoadingCEP, setIsLoadingCEP] = useState(false);
    const [activeTab, setActiveTab] = useState('dados');

    // Preencher formulário ao editar
    useEffect(() => {
        if (company) {
            setFormData({
                cnpj: company.cnpj || '',
                razao_social: company.razao_social || '',
                nome_fantasia: company.nome_fantasia || '',
                inscricao_estadual: company.inscricao_estadual || '',
                inscricao_municipal: company.inscricao_municipal || '',
                cep: company.cep || '',
                logradouro: company.logradouro || '',
                numero: company.numero || '',
                complemento: company.complemento || '',
                bairro: company.bairro || '',
                cidade: company.cidade || '',
                uf: company.uf || '',
                email: company.email || '',
                phone: company.phone || '',
                website: company.website || '',
                porte: company.porte || undefined,
                regime_tributario: company.regime_tributario || undefined,
                banco_padrao: company.banco_padrao || '',
                agencia_padrao: company.agencia_padrao || '',
                conta_padrao: company.conta_padrao || '',
            });
            setErrors({});
        } else {
            setFormData(EMPTY_FORM);
            setErrors({});
        }
        setActiveTab('dados');
    }, [company, open]);

    // Atualizar campo do formulário
    const handleChange = useCallback((field: keyof CreateCompanyDTO, value: string | CompanySize | TaxRegime | undefined) => {
        setFormData(prev => ({ ...prev, [field]: value }));

        // Limpar erro do campo
        if (errors[field]) {
            setErrors(prev => {
                const next = { ...prev };
                delete next[field];
                return next;
            });
        }
    }, [errors]);

    // Formatar CNPJ ao digitar
    const handleCNPJChange = useCallback((value: string) => {
        const formatted = formatCNPJ(value);
        handleChange('cnpj', formatted);

        // Validar ao completar
        if (value.replace(/\D/g, '').length === 14) {
            if (!validateCNPJ(value)) {
                setErrors(prev => ({ ...prev, cnpj: 'CNPJ inválido' }));
            }
        }
    }, [handleChange]);

    // Consultar CNPJ na Receita Federal
    const handleConsultarCNPJ = useCallback(async () => {
        if (!validateCNPJ(formData.cnpj)) {
            setErrors(prev => ({ ...prev, cnpj: 'CNPJ inválido' }));
            return;
        }

        setIsLoadingCNPJ(true);
        try {
            const data = await consultarCNPJ(formData.cnpj);
            if (data) {
                setFormData(prev => ({
                    ...prev,
                    ...data,
                    cnpj: prev.cnpj, // Manter CNPJ original formatado
                }));
                toast({
                    title: 'Dados carregados!',
                    description: 'Os dados da empresa foram preenchidos automaticamente.',
                });
            }
        } catch (error) {
            toast({
                title: 'Erro ao consultar CNPJ',
                description: error instanceof Error ? error.message : 'Tente novamente',
                variant: 'destructive',
            });
        } finally {
            setIsLoadingCNPJ(false);
        }
    }, [formData.cnpj, toast]);

    // Formatar CEP ao digitar
    const handleCEPChange = useCallback((value: string) => {
        const formatted = formatCEP(value);
        handleChange('cep', formatted);
    }, [handleChange]);

    // Consultar CEP
    const handleConsultarCEP = useCallback(async () => {
        if (!validateCEP(formData.cep || '')) {
            setErrors(prev => ({ ...prev, cep: 'CEP inválido' }));
            return;
        }

        setIsLoadingCEP(true);
        try {
            const data = await consultarCEP(formData.cep || '');
            if (data) {
                setFormData(prev => ({
                    ...prev,
                    logradouro: data.logradouro,
                    bairro: data.bairro,
                    cidade: data.cidade,
                    uf: data.uf,
                }));
                toast({
                    title: 'Endereço carregado!',
                    description: 'Os dados do endereço foram preenchidos automaticamente.',
                });
            }
        } catch (error) {
            toast({
                title: 'Erro ao consultar CEP',
                description: error instanceof Error ? error.message : 'Tente novamente',
                variant: 'destructive',
            });
        } finally {
            setIsLoadingCEP(false);
        }
    }, [formData.cep, toast]);

    // Formatar telefone ao digitar
    const handlePhoneChange = useCallback((value: string) => {
        const formatted = formatPhone(value);
        handleChange('phone', formatted);
    }, [handleChange]);

    // Validar formulário
    const validate = useCallback((): boolean => {
        const newErrors: Record<string, string> = {};

        // CNPJ obrigatório e válido
        if (!formData.cnpj) {
            newErrors.cnpj = 'CNPJ é obrigatório';
        } else if (!validateCNPJ(formData.cnpj)) {
            newErrors.cnpj = 'CNPJ inválido';
        }

        // Razão Social obrigatória
        if (!formData.razao_social?.trim()) {
            newErrors.razao_social = 'Razão Social é obrigatória';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    }, [formData]);

    // Submeter formulário
    const handleSubmit = useCallback(async (e: React.FormEvent) => {
        e.preventDefault();

        if (!validate()) {
            setActiveTab('dados');
            return;
        }

        setIsSubmitting(true);
        try {
            await onSave(formData);
            onOpenChange(false);
            toast({
                title: isEdit ? 'Empresa atualizada!' : 'Empresa criada!',
                description: isEdit
                    ? 'As informações da empresa foram atualizadas.'
                    : 'A nova empresa foi cadastrada com sucesso.',
            });
        } catch (error) {
            toast({
                title: 'Erro ao salvar',
                description: error instanceof Error ? error.message : 'Não foi possível salvar a empresa',
                variant: 'destructive',
            });
        } finally {
            setIsSubmitting(false);
        }
    }, [formData, isEdit, onOpenChange, onSave, toast, validate]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Building2 className="h-5 w-5" />
                        {isEdit ? 'Editar Empresa' : 'Nova Empresa'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEdit
                            ? 'Atualize as informações da empresa.'
                            : 'Preencha os dados da empresa. Campos com * são obrigatórios.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit}>
                    <Tabs value={activeTab} onValueChange={setActiveTab} className="mt-4">
                        <TabsList className="grid w-full grid-cols-4">
                            <TabsTrigger value="dados">
                                <Building2 className="h-4 w-4 mr-2" />
                                Dados
                            </TabsTrigger>
                            <TabsTrigger value="endereco">
                                <MapPin className="h-4 w-4 mr-2" />
                                Endereço
                            </TabsTrigger>
                            <TabsTrigger value="contato">
                                <Phone className="h-4 w-4 mr-2" />
                                Contato
                            </TabsTrigger>
                            <TabsTrigger value="bancario">
                                <CreditCard className="h-4 w-4 mr-2" />
                                Bancário
                            </TabsTrigger>
                        </TabsList>

                        {/* Tab Dados da Empresa */}
                        <TabsContent value="dados" className="space-y-4 mt-4">
                            {/* CNPJ */}
                            <div className="space-y-2">
                                <Label htmlFor="cnpj">CNPJ *</Label>
                                <div className="flex gap-2">
                                    <Input
                                        id="cnpj"
                                        placeholder="00.000.000/0000-00"
                                        value={formData.cnpj}
                                        onChange={(e) => handleCNPJChange(e.target.value)}
                                        disabled={isEdit}
                                        className={cn(errors.cnpj && "border-red-500")}
                                        maxLength={18}
                                    />
                                    {!isEdit && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            onClick={handleConsultarCNPJ}
                                            disabled={isLoadingCNPJ || !validateCNPJ(formData.cnpj)}
                                            title="Consultar CNPJ na Receita Federal"
                                        >
                                            {isLoadingCNPJ ? (
                                                <Loader2 className="h-4 w-4 animate-spin" />
                                            ) : (
                                                <Search className="h-4 w-4" />
                                            )}
                                        </Button>
                                    )}
                                </div>
                                {errors.cnpj && (
                                    <p className="text-sm text-red-500">{errors.cnpj}</p>
                                )}
                            </div>

                            {/* Razão Social */}
                            <div className="space-y-2">
                                <Label htmlFor="razao_social">Razão Social *</Label>
                                <Input
                                    id="razao_social"
                                    placeholder="Nome empresarial oficial"
                                    value={formData.razao_social}
                                    onChange={(e) => handleChange('razao_social', e.target.value)}
                                    className={cn(errors.razao_social && "border-red-500")}
                                />
                                {errors.razao_social && (
                                    <p className="text-sm text-red-500">{errors.razao_social}</p>
                                )}
                            </div>

                            {/* Nome Fantasia */}
                            <div className="space-y-2">
                                <Label htmlFor="nome_fantasia">Nome Fantasia</Label>
                                <Input
                                    id="nome_fantasia"
                                    placeholder="Nome comercial"
                                    value={formData.nome_fantasia}
                                    onChange={(e) => handleChange('nome_fantasia', e.target.value)}
                                />
                            </div>

                            {/* Inscrições */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="inscricao_estadual">Inscrição Estadual</Label>
                                    <Input
                                        id="inscricao_estadual"
                                        placeholder="I.E."
                                        value={formData.inscricao_estadual}
                                        onChange={(e) => handleChange('inscricao_estadual', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="inscricao_municipal">Inscrição Municipal</Label>
                                    <Input
                                        id="inscricao_municipal"
                                        placeholder="I.M."
                                        value={formData.inscricao_municipal}
                                        onChange={(e) => handleChange('inscricao_municipal', e.target.value)}
                                    />
                                </div>
                            </div>

                            {/* Classificação */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="porte">Porte</Label>
                                    <Select
                                        value={formData.porte || ''}
                                        onValueChange={(value) => handleChange('porte', value as CompanySize)}
                                    >
                                        <SelectTrigger id="porte">
                                            <SelectValue placeholder="Selecione o porte" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {(Object.keys(COMPANY_SIZE_LABELS) as CompanySize[]).map((key) => (
                                                <SelectItem key={key} value={key}>
                                                    {COMPANY_SIZE_LABELS[key]}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="regime_tributario">Regime Tributário</Label>
                                    <Select
                                        value={formData.regime_tributario || ''}
                                        onValueChange={(value) => handleChange('regime_tributario', value as TaxRegime)}
                                    >
                                        <SelectTrigger id="regime_tributario">
                                            <SelectValue placeholder="Selecione o regime" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {(Object.keys(TAX_REGIME_LABELS) as TaxRegime[]).map((key) => (
                                                <SelectItem key={key} value={key}>
                                                    {TAX_REGIME_LABELS[key]}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </TabsContent>

                        {/* Tab Endereço */}
                        <TabsContent value="endereco" className="space-y-4 mt-4">
                            {/* CEP */}
                            <div className="space-y-2">
                                <Label htmlFor="cep">CEP</Label>
                                <div className="flex gap-2">
                                    <Input
                                        id="cep"
                                        placeholder="00000-000"
                                        value={formData.cep}
                                        onChange={(e) => handleCEPChange(e.target.value)}
                                        maxLength={9}
                                        className={cn(errors.cep && "border-red-500")}
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        onClick={handleConsultarCEP}
                                        disabled={isLoadingCEP || !validateCEP(formData.cep || '')}
                                        title="Consultar CEP"
                                    >
                                        {isLoadingCEP ? (
                                            <Loader2 className="h-4 w-4 animate-spin" />
                                        ) : (
                                            <Search className="h-4 w-4" />
                                        )}
                                    </Button>
                                </div>
                                {errors.cep && (
                                    <p className="text-sm text-red-500">{errors.cep}</p>
                                )}
                            </div>

                            {/* Logradouro e Número */}
                            <div className="grid grid-cols-3 gap-4">
                                <div className="col-span-2 space-y-2">
                                    <Label htmlFor="logradouro">Logradouro</Label>
                                    <Input
                                        id="logradouro"
                                        placeholder="Rua, Av., etc."
                                        value={formData.logradouro}
                                        onChange={(e) => handleChange('logradouro', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="numero">Número</Label>
                                    <Input
                                        id="numero"
                                        placeholder="Nº"
                                        value={formData.numero}
                                        onChange={(e) => handleChange('numero', e.target.value)}
                                    />
                                </div>
                            </div>

                            {/* Complemento e Bairro */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="complemento">Complemento</Label>
                                    <Input
                                        id="complemento"
                                        placeholder="Sala, Andar, etc."
                                        value={formData.complemento}
                                        onChange={(e) => handleChange('complemento', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="bairro">Bairro</Label>
                                    <Input
                                        id="bairro"
                                        placeholder="Bairro"
                                        value={formData.bairro}
                                        onChange={(e) => handleChange('bairro', e.target.value)}
                                    />
                                </div>
                            </div>

                            {/* Cidade e UF */}
                            <div className="grid grid-cols-3 gap-4">
                                <div className="col-span-2 space-y-2">
                                    <Label htmlFor="cidade">Cidade</Label>
                                    <Input
                                        id="cidade"
                                        placeholder="Cidade"
                                        value={formData.cidade}
                                        onChange={(e) => handleChange('cidade', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="uf">UF</Label>
                                    <Select
                                        value={formData.uf || ''}
                                        onValueChange={(value) => handleChange('uf', value)}
                                    >
                                        <SelectTrigger id="uf">
                                            <SelectValue placeholder="UF" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {UF_OPTIONS.map((uf) => (
                                                <SelectItem key={uf} value={uf}>
                                                    {uf}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </TabsContent>

                        {/* Tab Contato */}
                        <TabsContent value="contato" className="space-y-4 mt-4">
                            <div className="space-y-2">
                                <Label htmlFor="phone">Telefone</Label>
                                <div className="relative">
                                    <Phone className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="phone"
                                        placeholder="(00) 00000-0000"
                                        value={formData.phone}
                                        onChange={(e) => handlePhoneChange(e.target.value)}
                                        className="pl-10"
                                        maxLength={15}
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="email">E-mail</Label>
                                <div className="relative">
                                    <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="email"
                                        type="email"
                                        placeholder="contato@empresa.com.br"
                                        value={formData.email}
                                        onChange={(e) => handleChange('email', e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="website">Website</Label>
                                <div className="relative">
                                    <Globe className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="website"
                                        type="url"
                                        placeholder="www.empresa.com.br"
                                        value={formData.website}
                                        onChange={(e) => handleChange('website', e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                            </div>
                        </TabsContent>

                        {/* Tab Dados Bancários */}
                        <TabsContent value="bancario" className="space-y-4 mt-4">
                            <p className="text-sm text-muted-foreground">
                                Dados bancários padrão para transações da empresa.
                            </p>

                            <div className="space-y-2">
                                <Label htmlFor="banco_padrao">Banco</Label>
                                <Input
                                    id="banco_padrao"
                                    placeholder="Nome do banco"
                                    value={formData.banco_padrao}
                                    onChange={(e) => handleChange('banco_padrao', e.target.value)}
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="agencia_padrao">Agência</Label>
                                    <Input
                                        id="agencia_padrao"
                                        placeholder="0000"
                                        value={formData.agencia_padrao}
                                        onChange={(e) => handleChange('agencia_padrao', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="conta_padrao">Conta</Label>
                                    <Input
                                        id="conta_padrao"
                                        placeholder="00000-0"
                                        value={formData.conta_padrao}
                                        onChange={(e) => handleChange('conta_padrao', e.target.value)}
                                    />
                                </div>
                            </div>
                        </TabsContent>
                    </Tabs>

                    <DialogFooter className="mt-6">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={isSubmitting}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={isSubmitting}>
                            {isSubmitting && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                            {isEdit ? 'Salvar Alterações' : 'Criar Empresa'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default CompanyDialog;
