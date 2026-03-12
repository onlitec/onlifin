import { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { DollarSign, Calendar, Tag, MessageSquare } from 'lucide-react';
import type { Transaction, Account, Category } from '@/types/types';
import { cn } from '@/lib/utils';

const transactionSchema = z.object({
    description: z.string().min(1, 'Descrição é obrigatória'),
    amount: z.string().min(1, 'Valor é obrigatório'),
    type: z.enum(['income', 'expense', 'transfer']),
    date: z.string().min(1, 'Data é obrigatória'),
    account_id: z.string().min(1, 'Conta é obrigatória'),
    category_id: z.string().optional().nullable(),
    transfer_destination_account_id: z.string().optional().nullable(),
    notes: z.string().optional().nullable(),
});

type TransactionFormValues = z.infer<typeof transactionSchema>;

interface TransactionDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    transaction: Transaction | null;
    accounts: Account[];
    categories: Category[];
    onSave: (data: any) => Promise<void>;
}

export function TransactionDialog({
    open,
    onOpenChange,
    transaction,
    accounts,
    categories,
    onSave,
}: TransactionDialogProps) {
    const [isSaving, setIsSaving] = useState(false);

    const form = useForm<TransactionFormValues>({
        resolver: zodResolver(transactionSchema) as any,
        defaultValues: {
            description: '',
            amount: '',
            type: 'expense',
            date: new Date().toISOString().split('T')[0],
            account_id: '',
            category_id: null,
            transfer_destination_account_id: null,
            notes: '',
        },
    });

    const transactionType = form.watch('type');

    useEffect(() => {
        if (open) {
            if (transaction) {
                form.reset({
                    description: transaction.description || '',
                    amount: transaction.amount.toString(),
                    type: transaction.type,
                    date: transaction.date.split('T')[0],
                    account_id: transaction.account_id || '',
                    category_id: transaction.category_id,
                    transfer_destination_account_id: transaction.transfer_destination_account_id,
                    notes: transaction.notes || '',
                });
            } else {
                form.reset({
                    description: '',
                    amount: '',
                    type: 'expense',
                    date: new Date().toISOString().split('T')[0],
                    account_id: accounts.length > 0 ? accounts[0].id : '',
                    category_id: null,
                    transfer_destination_account_id: null,
                    notes: '',
                });
            }
        }
    }, [open, transaction, form, accounts]);

    const handleSubmit = async (values: TransactionFormValues) => {
        setIsSaving(true);
        try {
            const payload = {
                ...values,
                amount: parseFloat(values.amount),
                is_transfer: values.type === 'transfer',
                category_id: values.category_id === 'null' ? null : values.category_id,
                transfer_destination_account_id: values.transfer_destination_account_id === 'null' ? null : values.transfer_destination_account_id,
                notes: values.notes || null,
            };
            await onSave(payload);
            onOpenChange(false);
        } catch (error) {
            console.error('Erro ao salvar transação:', error);
        } finally {
            setIsSaving(false);
        }
    };

    const filteredCategories = categories.filter(cat => 
        transactionType === 'transfer' ? false : cat.type === transactionType
    );

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent side="right" className="w-full sm:max-w-[800px] p-0 flex flex-col h-full bg-slate-50 border-l border-slate-200">
                <ScrollArea className="flex-1 w-full">
                    <div className="p-8 space-y-8">
                        <SheetHeader className="text-left">
                            <SheetTitle className="text-3xl font-black tracking-tighter uppercase text-slate-900 leading-none">
                                {transaction ? 'Editar Lançamento' : 'Novo Registro'}
                            </SheetTitle>
                            <SheetDescription className="text-xs uppercase tracking-widest font-bold text-slate-500 opacity-70">
                                {transaction ? 'Ajuste os parâmetros da operação selecionada.' : 'A precisão nos dados garante a soberania da sua gestão.'}
                            </SheetDescription>
                        </SheetHeader>

                        <Form {...form}>
                            <form onSubmit={form.handleSubmit(handleSubmit)} className="space-y-6 pb-8">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Tipo de Transação */}
                                    <FormField
                                        control={form.control}
                                        name="type"
                                        render={({ field }) => (
                                            <FormItem className="md:col-span-2 space-y-3">
                                                <FormLabel className="text-[10px] uppercase tracking-[0.2em] font-black text-slate-400 ml-1">Modalidade da Operação</FormLabel>
                                                <div className="flex p-1.5 bg-slate-200/50 rounded-2xl gap-1.5 border border-slate-200">
                                                    <button
                                                        type="button"
                                                        onClick={() => field.onChange('expense')}
                                                        className={cn(
                                                            "flex-1 py-3 px-3 rounded-xl text-[10px] uppercase tracking-widest font-black transition-all",
                                                            field.value === 'expense' 
                                                                ? "bg-white text-red-600 shadow-md shadow-red-500/10" 
                                                                : "text-slate-500 hover:text-slate-700"
                                                        )}
                                                    >
                                                        Despesa
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => field.onChange('income')}
                                                        className={cn(
                                                            "flex-1 py-3 px-3 rounded-xl text-[10px] uppercase tracking-widest font-black transition-all",
                                                            field.value === 'income' 
                                                                ? "bg-white text-emerald-600 shadow-md shadow-emerald-500/10" 
                                                                : "text-slate-500 hover:text-slate-700"
                                                        )}
                                                    >
                                                        Receita
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => field.onChange('transfer')}
                                                        className={cn(
                                                            "flex-1 py-3 px-3 rounded-xl text-[10px] uppercase tracking-widest font-black transition-all",
                                                            field.value === 'transfer' 
                                                                ? "bg-white text-blue-600 shadow-md shadow-blue-500/10" 
                                                                : "text-slate-500 hover:text-slate-700"
                                                        )}
                                                    >
                                                        Transferir
                                                    </button>
                                                </div>
                                            </FormItem>
                                        )}
                                    />

                                    {/* Descrição */}
                                    <FormField
                                        control={form.control}
                                        name="description"
                                        render={({ field }) => (
                                            <FormItem className="md:col-span-2 space-y-3">
                                                <FormLabel className="text-[10px] uppercase tracking-[0.2em] font-black text-slate-400 ml-1">Identificação do Lançamento</FormLabel>
                                                <FormControl>
                                                    <div className="relative group">
                                                        <Tag className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-slate-900 transition-colors" />
                                                        <Input 
                                                            placeholder="Ex: Assinatura Cloud, Proventos..." 
                                                            className="pl-12 h-14 bg-white border-slate-200 rounded-2xl font-bold text-slate-900 focus:ring-slate-900 text-lg"
                                                            {...field} 
                                                        />
                                                    </div>
                                                </FormControl>
                                                <FormMessage className="text-[10px] font-bold uppercase" />
                                            </FormItem>
                                        )}
                                    />

                                    {/* Valor */}
                                    <FormField
                                        control={form.control}
                                        name="amount"
                                        render={({ field }) => (
                                            <FormItem className="space-y-3">
                                                <FormLabel className="text-[10px] uppercase tracking-[0.2em] font-black text-slate-400 ml-1">Aporte de Capital</FormLabel>
                                                <FormControl>
                                                    <div className="relative group">
                                                        <DollarSign className="absolute left-4 top-1/2 -translate-y-1/2 w-6 h-6 text-slate-900" />
                                                        <Input 
                                                            type="number" 
                                                            step="0.01" 
                                                            placeholder="0,00" 
                                                            className="pl-12 h-14 bg-white border-slate-200 rounded-2xl text-xl font-black text-slate-900 tracking-tighter focus:ring-slate-900"
                                                            {...field} 
                                                        />
                                                    </div>
                                                </FormControl>
                                                <FormMessage className="text-[10px] font-bold uppercase" />
                                            </FormItem>
                                        )}
                                    />

                                    {/* Data */}
                                    <FormField
                                        control={form.control}
                                        name="date"
                                        render={({ field }) => (
                                            <FormItem className="space-y-3">
                                                <FormLabel className="text-[10px] uppercase tracking-[0.2em] font-black text-slate-400 ml-1">Calendário</FormLabel>
                                                <FormControl>
                                                    <div className="relative group">
                                                        <Calendar className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 transition-colors" />
                                                        <Input 
                                                            type="date" 
                                                            className="pl-12 h-14 bg-white border-slate-200 rounded-2xl font-black text-sm uppercase"
                                                            {...field} 
                                                        />
                                                    </div>
                                                </FormControl>
                                                <FormMessage className="text-[10px] font-bold uppercase" />
                                            </FormItem>
                                        )}
                                    />

                                    {/* Conta Origem */}
                                    <FormField
                                        control={form.control}
                                        name="account_id"
                                        render={({ field }) => (
                                            <FormItem className="space-y-3">
                                                <FormLabel className="text-[10px] uppercase tracking-[0.2em] font-black text-slate-400 ml-1">{transactionType === 'transfer' ? 'Recurso de Origem' : 'Repositório Financeiro'}</FormLabel>
                                                <Select onValueChange={field.onChange} defaultValue={field.value} value={field.value || ''}>
                                                    <FormControl>
                                                        <SelectTrigger className="h-14 bg-white border-slate-200 rounded-2xl font-black text-slate-900">
                                                            <SelectValue placeholder="Selecione..." />
                                                        </SelectTrigger>
                                                    </FormControl>
                                                    <SelectContent className="rounded-2xl border-slate-200 shadow-2xl">
                                                        {accounts.map(acc => (
                                                            <SelectItem key={acc.id} value={acc.id} className="font-bold py-3 uppercase text-xs tracking-widest">{acc.name}</SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <FormMessage className="text-[10px] font-bold uppercase" />
                                            </FormItem>
                                        )}
                                    />

                                    {/* Categoria ou Conta Destino */}
                                    {transactionType === 'transfer' ? (
                                        <FormField
                                            control={form.control}
                                            name="transfer_destination_account_id"
                                            render={({ field }) => (
                                                <FormItem className="space-y-3">
                                                    <FormLabel className="text-[10px] uppercase tracking-[0.2em] font-black text-slate-400 ml-1">Recurso de Destino</FormLabel>
                                                    <Select onValueChange={field.onChange} defaultValue={field.value || undefined} value={field.value || ''}>
                                                        <FormControl>
                                                            <SelectTrigger className="h-14 bg-white border-slate-200 rounded-2xl font-black text-slate-900">
                                                                <SelectValue placeholder="Selecione conta destino" />
                                                            </SelectTrigger>
                                                        </FormControl>
                                                        <SelectContent className="rounded-2xl border-slate-200 shadow-2xl">
                                                            {accounts.map(acc => (
                                                                <SelectItem key={acc.id} value={acc.id} className="font-bold py-3 uppercase text-xs tracking-widest">{acc.name}</SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <FormMessage className="text-[10px] font-bold uppercase" />
                                                </FormItem>
                                            )}
                                        />
                                    ) : (
                                        <FormField
                                            control={form.control}
                                            name="category_id"
                                            render={({ field }) => (
                                                <FormItem className="space-y-3">
                                                    <FormLabel className="text-[10px] uppercase tracking-[0.2em] font-black text-slate-400 ml-1">Domínio Estratégico</FormLabel>
                                                    <Select onValueChange={field.onChange} defaultValue={field.value || undefined} value={field.value || ''}>
                                                        <FormControl>
                                                            <SelectTrigger className="h-14 bg-white border-slate-200 rounded-2xl font-black text-slate-900">
                                                                <SelectValue placeholder="Selecione categoria" />
                                                            </SelectTrigger>
                                                        </FormControl>
                                                        <SelectContent className="rounded-2xl border-slate-200 shadow-2xl max-h-[300px]">
                                                            <SelectItem value="null" className="font-bold py-3 uppercase text-xs tracking-widest text-slate-400">Sem categoria</SelectItem>
                                                            {filteredCategories.map(cat => (
                                                                <SelectItem key={cat.id} value={cat.id} className="font-bold py-3 uppercase text-xs tracking-widest">
                                                                    <span className="mr-2">{cat.icon}</span> {cat.name}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <FormMessage className="text-[10px] font-bold uppercase" />
                                                </FormItem>
                                            )}
                                        />
                                    )}

                                    {/* Observações */}
                                    <FormField
                                        control={form.control}
                                        name="notes"
                                        render={({ field }) => (
                                            <FormItem className="md:col-span-2 space-y-3">
                                                <FormLabel className="text-[10px] uppercase tracking-[0.2em] font-black text-slate-400 ml-1">Notas de Inteligência</FormLabel>
                                                <FormControl>
                                                    <div className="relative group">
                                                        <MessageSquare className="absolute left-4 top-4 w-5 h-5 text-slate-400 group-focus-within:text-slate-900 transition-colors" />
                                                        <textarea 
                                                            placeholder="Contexto adicional da operação..." 
                                                            className="w-full pl-12 pr-4 py-4 bg-white border border-slate-200 rounded-2xl font-bold text-slate-900 text-sm min-h-[100px] focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent transition-all"
                                                            {...field} 
                                                            value={field.value || ''}
                                                        />
                                                    </div>
                                                </FormControl>
                                                <FormMessage className="text-[10px] font-bold uppercase" />
                                            </FormItem>
                                        )}
                                    />
                                </div>

                                <div className="flex gap-4 pt-4 border-t border-slate-200">
                                    <Button 
                                        type="button" 
                                        variant="outline" 
                                        className="flex-1 h-14 rounded-2xl border-slate-200 font-black text-[10px] uppercase tracking-[0.2em] text-slate-400 hover:bg-slate-100 transition-all" 
                                        onClick={() => onOpenChange(false)}
                                    >
                                        Cancelar
                                    </Button>
                                    <Button 
                                        type="submit" 
                                        disabled={isSaving} 
                                        className="flex-[2] h-14 rounded-2xl bg-slate-900 hover:bg-black text-[10px] uppercase tracking-[0.2em] font-black text-white shadow-xl shadow-slate-900/20 transition-all hover:scale-[1.02] active:scale-[0.98]"
                                    >
                                        {isSaving ? 'Salvando Operação...' : 'Confirmar Lançamento'}
                                    </Button>
                                </div>
                            </form>
                        </Form>
                    </div>
                </ScrollArea>
            </SheetContent>
        </Sheet>
    );
}
