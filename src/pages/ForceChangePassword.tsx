import * as React from 'react';
import { useNavigate } from 'react-router-dom';
import { supabase } from '@/db/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/hooks/use-toast';
import { Loader2, ShieldAlert } from 'lucide-react';
import { validatePassword } from '@/utils/security';

export default function ForceChangePassword() {
    const [currentPassword, setCurrentPassword] = React.useState('');
    const [newPassword, setNewPassword] = React.useState('');
    const [confirmPassword, setConfirmPassword] = React.useState('');
    const [isLoading, setIsLoading] = React.useState(false);
    const navigate = useNavigate();
    const { toast } = useToast();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsLoading(true);

        if (newPassword !== confirmPassword) {
            toast({
                title: 'Erro',
                description: 'As senhas não coincidem',
                variant: 'destructive'
            });
            setIsLoading(false);
            return;
        }

        const passwordValidation = validatePassword(newPassword);
        if (!passwordValidation.valid) {
            toast({
                title: 'Erro',
                description: passwordValidation.message,
                variant: 'destructive'
            });
            setIsLoading(false);
            return;
        }

        try {
            const { data, error } = await supabase.rpc('change_own_password', {
                p_current_password: currentPassword,
                p_new_password: newPassword
            });

            if (error) throw error;

            toast({
                title: 'Sucesso!',
                description: 'Sua senha foi alterada com sucesso.'
            });

            navigate('/');
        } catch (error: any) {
            toast({
                title: 'Erro ao alterar senha',
                description: error.message || 'Verifique sua senha atual e tente novamente.',
                variant: 'destructive'
            });
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-background p-4">
            <Card className="w-full max-w-md border-primary/50 shadow-lg shadow-primary/10">
                <CardHeader className="space-y-1 text-center">
                    <div className="flex justify-center mb-4">
                        <div className="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center">
                            <ShieldAlert className="h-8 w-8 text-primary animate-pulse" />
                        </div>
                    </div>
                    <CardTitle className="text-2xl font-bold">Troca de Senha Obrigatória</CardTitle>
                    <CardDescription>
                        Sua conta foi marcada para troca obrigatória de senha por segurança.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="currentPassword">Senha Atual (Provisória)</Label>
                            <Input
                                id="currentPassword"
                                type="password"
                                value={currentPassword}
                                onChange={(e) => setCurrentPassword(e.target.value)}
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="newPassword">Nova Senha</Label>
                            <Input
                                id="newPassword"
                                type="password"
                                value={newPassword}
                                onChange={(e) => setNewPassword(e.target.value)}
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="confirmPassword">Confirmar Nova Senha</Label>
                            <Input
                                id="confirmPassword"
                                type="password"
                                value={confirmPassword}
                                onChange={(e) => setConfirmPassword(e.target.value)}
                                required
                            />
                        </div>
                        <Button
                            type="submit"
                            className="w-full mt-4"
                            disabled={isLoading}
                        >
                            {isLoading ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Alterando...
                                </>
                            ) : (
                                'Salvar Nova Senha'
                            )}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}
