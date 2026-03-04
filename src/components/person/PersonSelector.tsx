/**
 * Componente de seleção de pessoa (Membro da Família)
 * 
 * Dropdown para selecionar a pessoa ativa no contexto PF.
 */

import { User, ChevronDown, Plus } from 'lucide-react';
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
                    className={cn("gap-2 justify-between min-w-[180px] glass-card shadow-lg hover:bg-white/10 transition-all border-white/10", className)}
                >
                    <div className="flex items-center gap-2 truncate">
                        {selectedPerson?.color ? (
                            <div
                                className="h-3 w-3 rounded-full shrink-0 border border-white/20 shadow-[0_0_8px_rgba(var(--primary),0.5)]"
                                style={{ backgroundColor: selectedPerson.color }}
                            />
                        ) : (
                            <User className="h-4 w-4 shrink-0 opacity-70" />
                        )}
                        <span className="truncate font-black tracking-tighter uppercase text-[10px]">
                            {currentName}
                        </span>
                    </div>
                    <ChevronDown className="h-3 w-3 shrink-0 opacity-30" />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="start" className="w-64 glass-card premium-card border-white/10 backdrop-blur-2xl p-2">
                <DropdownMenuLabel className="text-[10px] font-black uppercase tracking-widest text-muted-foreground pb-2 px-2">Family Members</DropdownMenuLabel>
                <DropdownMenuSeparator className="bg-white/5 mx--2" />

                <div className="space-y-1 mt-1">
                    {people.map((person) => (
                        <DropdownMenuItem
                            key={person.id}
                            className={cn(
                                "flex items-center gap-3 cursor-pointer rounded-xl transition-all duration-200 py-2.5 px-3 hover:bg-white/5",
                                selectedPerson?.id === person.id && "bg-primary/10 border-white/5 shadow-inner"
                            )}
                            onClick={() => selectPerson(person.id)}
                        >
                            <div
                                className="h-4 w-4 rounded-full shrink-0 border-2 border-white/10 shadow-lg transition-transform group-hover:scale-110"
                                style={{ backgroundColor: person.color || '#10b981' }}
                            />
                            <span className="font-bold tracking-tight truncate flex-1 text-sm uppercase">
                                {person.name}
                            </span>
                            {selectedPerson?.id === person.id && (
                                <div className="h-2 w-2 rounded-full bg-primary animate-pulse" />
                            )}
                        </DropdownMenuItem>
                    ))}
                </div>

                <DropdownMenuSeparator className="bg-white/5 mx--2 mt-2" />
                <DropdownMenuItem
                    className="cursor-pointer mt-1 rounded-xl py-2.5 px-3 hover:bg-primary/20 text-primary transition-colors font-bold uppercase text-[10px] tracking-widest"
                    onClick={() => navigate('/pf/people')}
                >
                    <Plus className="h-4 w-4 mr-2" />
                    <span>Manage Family</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
