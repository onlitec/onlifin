/**
 * Componente de seleção de pessoa (Membro da Família)
 * 
 * Dropdown para selecionar a pessoa ativa no contexto PF.
 */

import { User, ChevronDown, Plus, Check } from 'lucide-react';
import { usePerson } from '@/contexts/PersonContext';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import { useNavigate } from 'react-router-dom';

interface PersonSelectorProps {
    className?: string;
    variant?: 'default' | 'outline' | 'ghost';
    size?: 'default' | 'sm' | 'lg';
}

export function PersonSelector({
    className,
    variant = 'outline',
    size = 'default',
}: PersonSelectorProps) {
    const {
        people,
        selectedPerson,
        isLoadingPeople,
        selectPerson
    } = usePerson();
    const navigate = useNavigate();

    // Loading state
    if (isLoadingPeople) {
        return (
            <Skeleton className={cn("h-10 w-48", className)} />
        );
    }

    const currentName = selectedPerson ? selectedPerson.name : (people.find(p => p.is_default)?.name || 'Titular');

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant={variant}
                    size={size}
                    className={cn("gap-2 justify-between min-w-[180px]", className)}
                >
                    <div className="flex items-center gap-2 truncate">
                        <User className="h-4 w-4 shrink-0" />
                        <span className="truncate">
                            {currentName}
                        </span>
                    </div>
                    <ChevronDown className="h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="start" className="w-60">
                <DropdownMenuLabel>Membros da Família</DropdownMenuLabel>
                <DropdownMenuSeparator />

                {people.map((person) => (
                    <DropdownMenuItem
                        key={person.id}
                        className={cn(
                            "flex items-center gap-2 cursor-pointer",
                            selectedPerson?.id === person.id && "bg-accent"
                        )}
                        onClick={() => selectPerson(person.id)}
                    >
                        <User className="h-4 w-4 shrink-0 text-muted-foreground" />
                        <span className="font-medium truncate flex-1">
                            {person.name}
                        </span>
                        {/* {person.is_default && (
                             <Badge variant="secondary" className="text-[10px] h-4">Padrão</Badge>
                        )} */}
                        {selectedPerson?.id === person.id && (
                            <Check className="h-4 w-4 text-primary" />
                        )}
                    </DropdownMenuItem>
                ))}

                <DropdownMenuSeparator />
                <DropdownMenuItem
                    className="cursor-pointer"
                    onClick={() => navigate('/pf/people')}
                >
                    <Plus className="h-4 w-4 mr-2" />
                    <span>Gerenciar Pessoas</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
