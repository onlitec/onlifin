import { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import type { Person, CreatePersonDTO, UpdatePersonDTO } from '@/types/person';

const personSchema = z.object({
    name: z.string().min(2, 'Nome deve ter pelo menos 2 caracteres'),
    cpf: z.string().optional(),
    email: z.string().email('E-mail inválido').optional().or(z.literal('')),
    is_default: z.boolean().default(false),
});

type PersonFormValues = z.infer<typeof personSchema>;

interface PersonDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    person: Person | null;
    onSave: (data: CreatePersonDTO | UpdatePersonDTO) => Promise<void>;
}

export function PersonDialog({
    open,
    onOpenChange,
    person,
    onSave,
}: PersonDialogProps) {
    const [isSaving, setIsSaving] = useState(false);

    const form = useForm<PersonFormValues>({
        resolver: zodResolver(personSchema) as any, // Bypass strict type check for now
        defaultValues: {
            name: '',
            cpf: '',
            email: '',
            is_default: false,
        },
    });

    // Reset form when opening/changing person
    useEffect(() => {
        if (open) {
            form.reset({
                name: person?.name || '',
                cpf: person?.cpf || '',
                email: person?.email || '',
                is_default: person?.is_default || false,
            });
        }
    }, [open, person, form]);

    const handleSubmit = async (values: PersonFormValues) => {
        setIsSaving(true);
        try {
            await onSave(values);
            onOpenChange(false);
        } catch (error) {
            console.error('Erro ao salvar:', error);
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>
                        {person ? 'Editar Pessoa' : 'Nova Pessoa'}
                    </DialogTitle>
                    <DialogDescription>
                        {person
                            ? 'Edite os dados do membro da família.'
                            : 'Adicione um novo membro da família para gerenciar finanças.'}
                    </DialogDescription>
                </DialogHeader>

                <Form {...form}>
                    <form onSubmit={form.handleSubmit(handleSubmit)} className="space-y-4">
                        <FormField
                            control={form.control}
                            name="name"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Nome</FormLabel>
                                    <FormControl>
                                        <Input placeholder="Ex: Maria Maria" {...field} />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />

                        <FormField
                            control={form.control}
                            name="cpf"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>CPF (Opcional)</FormLabel>
                                    <FormControl>
                                        <Input placeholder="000.000.000-00" {...field} />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />

                        <FormField
                            control={form.control}
                            name="email"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>E-mail (Opcional)</FormLabel>
                                    <FormControl>
                                        <Input type="email" placeholder="email@exemplo.com" {...field} />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />

                        <FormField
                            control={form.control}
                            name="is_default"
                            render={({ field }) => (
                                <FormItem className="flex flex-row items-start space-x-3 space-y-0 rounded-md border p-4">
                                    <FormControl>
                                        <Checkbox
                                            checked={field.value}
                                            onCheckedChange={field.onChange}
                                        />
                                    </FormControl>
                                    <div className="space-y-1 leading-none">
                                        <FormLabel>
                                            Padrão
                                        </FormLabel>
                                        <p className="text-sm text-muted-foreground">
                                            Definir como pessoa principal selecionada ao entrar.
                                        </p>
                                    </div>
                                </FormItem>
                            )}
                        />

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={isSaving}>
                                {isSaving ? 'Salvando...' : 'Salvar'}
                            </Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
}
