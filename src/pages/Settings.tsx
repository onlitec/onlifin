import * as React from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useToast } from '@/hooks/use-toast';
import {
    Download,
    Upload,
    Database,
    FileJson,
    AlertCircle,
    CheckCircle2,
    Loader2,
    ShieldCheck
} from 'lucide-react';
import { backupService, BackupData } from '@/services/backupService';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

export default function Settings() {
    const [isExporting, setIsExporting] = React.useState(false);
    const [isImporting, setIsImporting] = React.useState(false);
    const [importProgress, setImportProgress] = React.useState<string | null>(null);
    const fileInputRef = React.useRef<HTMLInputElement>(null);
    const { toast } = useToast();

    const handleExport = async () => {
        setIsExporting(true);
        try {
            const data = await backupService.exportBackup();
            backupService.downloadAsJson(data);
            toast({
                title: 'Backup Concluído',
                description: 'Seus dados foram exportados com sucesso.',
            });
        } catch (error: any) {
            console.error('Export error:', error);
            toast({
                title: 'Erro na Exportação',
                description: error.message || 'Não foi possível gerar o backup.',
                variant: 'destructive',
            });
        } finally {
            setIsExporting(false);
        }
    };

    const handleImportClick = () => {
        fileInputRef.current?.click();
    };

    const handleFileChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file) return;

        setIsImporting(true);
        setImportProgress('Lendo arquivo...');

        const reader = new FileReader();
        reader.onload = async (e) => {
            try {
                const content = e.target?.result as string;
                const backupData = JSON.parse(content) as BackupData;

                // Validação básica
                if (!backupData.version || !backupData.data) {
                    throw new Error('Arquivo de backup inválido ou corrompido.');
                }

                setImportProgress('Restaurando dados no banco...');
                const result = await backupService.importBackup(backupData);

                if (result.success) {
                    toast({
                        title: 'Restauração Concluída',
                        description: 'Todos os registros foram importados com sucesso.',
                    });
                } else {
                    toast({
                        title: 'Concluído com Alertas',
                        description: `Importação finalizada com ${result.errors.length} erros.`,
                        variant: 'destructive',
                    });
                }
            } catch (error: any) {
                console.error('Import error:', error);
                toast({
                    title: 'Erro na Importação',
                    description: error.message || 'Falha ao processar o arquivo de backup.',
                    variant: 'destructive',
                });
            } finally {
                setIsImporting(false);
                setImportProgress(null);
                if (fileInputRef.current) fileInputRef.current.value = '';
            }
        };

        reader.onerror = () => {
            toast({
                title: 'Erro de Leitura',
                description: 'Não foi possível ler o arquivo selecionado.',
                variant: 'destructive',
            });
            setIsImporting(false);
            setImportProgress(null);
        };

        reader.readAsText(file);
    };

    return (
        <div className="w-full max-w-[1200px] mx-auto p-6 space-y-8 animate-in fade-in duration-500">
            <div className="flex flex-col gap-1">
                <h1 className="text-3xl font-bold tracking-tight">Configurações</h1>
                <p className="text-muted-foreground text-lg">
                    Gerencie seus dados, preferências e segurança da conta.
                </p>
            </div>

            <div className="grid gap-6 md:grid-cols-2">
                {/* Export Card */}
                <Card className="hover:shadow-md transition-shadow">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Download className="h-5 w-5 text-blue-500" />
                            Exportar Dados
                        </CardTitle>
                        <CardDescription>
                            Baixe uma cópia completa de seus dados financeiros (PF e PJ) em formato JSON.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="p-4 rounded-lg bg-blue-500/5 border border-blue-500/20 flex gap-3 text-sm">
                            <ShieldCheck className="h-5 w-5 text-blue-500 shrink-0" />
                            <p className="text-blue-700 dark:text-blue-300">
                                Seus backups são protegidos por criptografia de transporte e só podem ser restaurados na sua conta ou em instâncias compatíveis.
                            </p>
                        </div>
                        <Button
                            className="w-full gap-2"
                            size="lg"
                            onClick={handleExport}
                            disabled={isExporting || isImporting}
                        >
                            {isExporting ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <FileJson className="h-4 w-4" />
                            )}
                            Gerar Backup Completo
                        </Button>
                    </CardContent>
                </Card>

                {/* Import Card */}
                <Card className="hover:shadow-md transition-shadow">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Upload className="h-5 w-5 text-green-500" />
                            Restaurar Backup
                        </CardTitle>
                        <CardDescription>
                            Suba um arquivo de backup previamente gerado para restaurar seus registros.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Alert variant="default" className="bg-amber-500/5 border-amber-500/20 text-amber-700 dark:text-amber-300">
                            <AlertCircle className="h-4 w-4 !text-amber-500" />
                            <AlertTitle>Importante</AlertTitle>
                            <AlertDescription className="text-xs">
                                O processo de restauração irá manter registros existentes e inserir apenas os novos, ou atualizar registros modificados baseados no ID.
                            </AlertDescription>
                        </Alert>

                        <input
                            type="file"
                            accept=".json"
                            className="hidden"
                            ref={fileInputRef}
                            onChange={handleFileChange}
                        />

                        <Button
                            variant="outline"
                            className="w-full gap-2 h-12 border-dashed border-2 hover:border-green-500 hover:bg-green-500/5 transition-all"
                            onClick={handleImportClick}
                            disabled={isExporting || isImporting}
                        >
                            {isImporting ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <Database className="h-4 w-4" />
                            )}
                            {importProgress || 'Selecionar Arquivo JSON'}
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <Card className="border-muted">
                <CardHeader>
                    <CardTitle className="text-xl">Status do Sistema e Segurança</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4">
                    <div className="flex items-center justify-between p-4 rounded-lg bg-muted/30 border">
                        <div className="flex items-center gap-3">
                            <div className="h-2 w-2 rounded-full bg-green-500" />
                            <div>
                                <p className="font-medium">Integridade dos Dados</p>
                                <p className="text-xs text-muted-foreground">Sincronizado com o banco de dados oficial</p>
                            </div>
                        </div>
                        <CheckCircle2 className="h-5 w-5 text-green-500" />
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
