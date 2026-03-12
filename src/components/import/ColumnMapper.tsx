import * as React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { CheckCircle2, XCircle } from 'lucide-react';
import type { CSVColumnMapping } from '@/utils/csvParser';

interface ColumnMapperProps {
  headers: string[];
  previewRows: string[][];
  detectedMapping: CSVColumnMapping | null;
  onMappingChange: (mapping: CSVColumnMapping) => void;
}

export function ColumnMapper({ headers, previewRows, detectedMapping, onMappingChange }: ColumnMapperProps) {
  const [mapping, setMapping] = React.useState<CSVColumnMapping>(
    detectedMapping || { date: 0, description: 1, amount: 2 }
  );

  React.useEffect(() => {
    onMappingChange(mapping);
  }, [mapping, onMappingChange]);

  const handleColumnChange = (field: keyof CSVColumnMapping, value: string) => {
    const index = parseInt(value);
    setMapping(prev => ({ ...prev, [field]: index }));
  };

  const isValid = mapping.date !== undefined && mapping.description !== undefined && mapping.amount !== undefined;

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          {isValid ? (
            <CheckCircle2 className="h-5 w-5 text-green-500" />
          ) : (
            <XCircle className="h-5 w-5 text-red-500" />
          )}
          Mapeamento de Colunas
        </CardTitle>
        <CardDescription>
          {detectedMapping 
            ? 'Colunas detectadas automaticamente. Ajuste se necessário.' 
            : 'Configure manualmente as colunas do seu arquivo CSV.'}
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Seletores de Colunas */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {/* Data */}
          <div className="space-y-2">
            <Label htmlFor="date-column" className="text-sm font-medium">
              Data <span className="text-red-500">*</span>
            </Label>
            <Select 
              value={mapping.date?.toString()} 
              onValueChange={(v) => handleColumnChange('date', v)}
            >
              <SelectTrigger id="date-column">
                <SelectValue placeholder="Selecione a coluna" />
              </SelectTrigger>
              <SelectContent>
                {headers.map((header, idx) => (
                  <SelectItem key={idx} value={idx.toString()}>
                    {header || `Coluna ${idx + 1}`}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {/* Descrição */}
          <div className="space-y-2">
            <Label htmlFor="description-column" className="text-sm font-medium">
              Descrição <span className="text-red-500">*</span>
            </Label>
            <Select 
              value={mapping.description?.toString()} 
              onValueChange={(v) => handleColumnChange('description', v)}
            >
              <SelectTrigger id="description-column">
                <SelectValue placeholder="Selecione a coluna" />
              </SelectTrigger>
              <SelectContent>
                {headers.map((header, idx) => (
                  <SelectItem key={idx} value={idx.toString()}>
                    {header || `Coluna ${idx + 1}`}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {/* Valor */}
          <div className="space-y-2">
            <Label htmlFor="amount-column" className="text-sm font-medium">
              Valor <span className="text-red-500">*</span>
            </Label>
            <Select 
              value={mapping.amount?.toString()} 
              onValueChange={(v) => handleColumnChange('amount', v)}
            >
              <SelectTrigger id="amount-column">
                <SelectValue placeholder="Selecione a coluna" />
              </SelectTrigger>
              <SelectContent>
                {headers.map((header, idx) => (
                  <SelectItem key={idx} value={idx.toString()}>
                    {header || `Coluna ${idx + 1}`}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>

        {/* Colunas Opcionais */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {/* Tipo */}
          <div className="space-y-2">
            <Label htmlFor="type-column" className="text-sm font-medium text-slate-600">
              Tipo (opcional)
            </Label>
            <Select 
              value={mapping.type?.toString() || 'none'} 
              onValueChange={(v) => handleColumnChange('type', v === 'none' ? '-1' : v)}
            >
              <SelectTrigger id="type-column">
                <SelectValue placeholder="Nenhuma" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="none">Nenhuma (detectar automaticamente)</SelectItem>
                {headers.map((header, idx) => (
                  <SelectItem key={idx} value={idx.toString()}>
                    {header || `Coluna ${idx + 1}`}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {/* Saldo */}
          <div className="space-y-2">
            <Label htmlFor="balance-column" className="text-sm font-medium text-slate-600">
              Saldo (opcional)
            </Label>
            <Select 
              value={mapping.balance?.toString() || 'none'} 
              onValueChange={(v) => handleColumnChange('balance', v === 'none' ? '-1' : v)}
            >
              <SelectTrigger id="balance-column">
                <SelectValue placeholder="Nenhuma" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="none">Nenhuma</SelectItem>
                {headers.map((header, idx) => (
                  <SelectItem key={idx} value={idx.toString()}>
                    {header || `Coluna ${idx + 1}`}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>

        {/* Preview */}
        <div className="mt-6">
          <Label className="text-sm font-medium mb-2 block">Preview dos Dados</Label>
          <div className="border rounded-lg overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow>
                  {headers.map((header, idx) => (
                    <TableHead key={idx} className={`
                      ${idx === mapping.date ? 'bg-blue-50' : ''}
                      ${idx === mapping.description ? 'bg-green-50' : ''}
                      ${idx === mapping.amount ? 'bg-purple-50' : ''}
                      ${idx === mapping.type ? 'bg-amber-50' : ''}
                      ${idx === mapping.balance ? 'bg-slate-50' : ''}
                    `}>
                      {header || `Col ${idx + 1}`}
                      {idx === mapping.date && <span className="ml-1 text-xs text-blue-600">(Data)</span>}
                      {idx === mapping.description && <span className="ml-1 text-xs text-green-600">(Desc)</span>}
                      {idx === mapping.amount && <span className="ml-1 text-xs text-purple-600">(Valor)</span>}
                      {idx === mapping.type && <span className="ml-1 text-xs text-amber-600">(Tipo)</span>}
                      {idx === mapping.balance && <span className="ml-1 text-xs text-slate-600">(Saldo)</span>}
                    </TableHead>
                  ))}
                </TableRow>
              </TableHeader>
              <TableBody>
                {previewRows.slice(0, 3).map((row, rowIdx) => (
                  <TableRow key={rowIdx}>
                    {row.map((cell, cellIdx) => (
                      <TableCell key={cellIdx} className={`
                        text-xs
                        ${cellIdx === mapping.date ? 'bg-blue-50/50' : ''}
                        ${cellIdx === mapping.description ? 'bg-green-50/50' : ''}
                        ${cellIdx === mapping.amount ? 'bg-purple-50/50' : ''}
                        ${cellIdx === mapping.type ? 'bg-amber-50/50' : ''}
                        ${cellIdx === mapping.balance ? 'bg-slate-50/50' : ''}
                      `}>
                        {cell}
                      </TableCell>
                    ))}
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
