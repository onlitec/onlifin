import { Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTheme } from '@/contexts/ThemeContext';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

export function ThemeToggle() {
    const { theme, toggleTheme } = useTheme();

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={toggleTheme}
                    className="h-9 w-9 text-slate-300 hover:text-slate-100 hover:bg-slate-700"
                >
                    {theme === 'dark' ? (
                        <Sun className="h-5 w-5" />
                    ) : (
                        <Moon className="h-5 w-5" />
                    )}
                    <span className="sr-only">
                        {theme === 'dark' ? 'Mudar para modo claro' : 'Mudar para modo escuro'}
                    </span>
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                <p>{theme === 'dark' ? 'Modo claro' : 'Modo escuro'}</p>
            </TooltipContent>
        </Tooltip>
    );
}
