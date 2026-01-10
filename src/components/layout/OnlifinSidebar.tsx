import * as React from 'react';
import { Link, useLocation } from 'react-router-dom';
import {
    Home,
    ArrowLeftRight,
    Building2,
    CreditCard,
    PieChart,
    BarChart3,
    Settings,
    ChevronUp,
    ChevronDown,
    User,
    TrendingUp,
    FileText,
    FilePlus,
    RefreshCw,
    Receipt,
    DollarSign,
    Users,
    Bot,
    Sliders,
    Layers
} from 'lucide-react';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar
} from '@/components/ui/sidebar';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAuth } from 'miaoda-auth-react';
import { APP_VERSION } from '@/config/version';

// Menu items simples
const simpleMenuItems = [
    {
        title: 'Dashboard',
        icon: Home,
        path: '/',
    },
    {
        title: 'Contas',
        icon: Building2,
        path: '/accounts',
    },
    {
        title: 'Cartões',
        icon: CreditCard,
        path: '/cards',
    },
];

// Transações com submenus
const transacoesSubmenus = [
    { title: 'Transações', icon: ArrowLeftRight, path: '/transactions' },
    { title: 'Contas a Pagar', icon: Receipt, path: '/bills-to-pay' },
    { title: 'Contas a Receber', icon: DollarSign, path: '/bills-to-receive' },
    { title: 'Importar Extrato', icon: FileText, path: '/import-statements' },
    { title: 'Importar', icon: FilePlus, path: '/import' },
    { title: 'Conciliação', icon: RefreshCw, path: '/reconciliation' },
];

// Itens após transações
const afterTransacoesItems = [
    {
        title: 'Relatórios',
        icon: BarChart3,
        path: '/reports',
    },
    {
        title: 'Previsão Financeira',
        icon: TrendingUp,
        path: '/forecast',
    },
];

// Admin submenus
const adminSubmenus = [
    { title: 'Geral', icon: Sliders, path: '/admin-general' },
    { title: 'Categorias', icon: Layers, path: '/categories' },
    { title: 'Assistente IA', icon: Bot, path: '/chat' },
    { title: 'Gestão de Usuários', icon: Users, path: '/user-management' },
    { title: 'Configuração IA', icon: Settings, path: '/ai-admin' },
];

export function OnlifinSidebar() {
    const location = useLocation();
    const { user, logout } = useAuth();
    const { state } = useSidebar();
    const [transacoesOpen, setTransacoesOpen] = React.useState(false);
    const [adminOpen, setAdminOpen] = React.useState(false);

    // Abrir menu de transações automaticamente se em uma página de transação
    React.useEffect(() => {
        const isInTransacoes = transacoesSubmenus.some(item => location.pathname === item.path);
        if (isInTransacoes) setTransacoesOpen(true);

        const isInAdmin = adminSubmenus.some(item => location.pathname === item.path);
        if (isInAdmin) setAdminOpen(true);
    }, [location.pathname]);

    const isActive = (path: string) => location.pathname === path;
    const isGroupActive = (items: { path: string }[]) =>
        items.some(item => location.pathname === item.path);

    return (
        <Sidebar collapsible="icon" className="border-r border-border">
            <SidebarHeader className="border-b border-border p-4">
                <div className="flex items-center gap-2">
                    <div className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <span className="text-lg font-bold">O</span>
                    </div>
                    {state === 'expanded' && (
                        <div className="flex flex-col">
                            <span className="text-sm font-semibold text-foreground">Onlifin</span>
                            <span className="text-xs text-muted-foreground">Personal Finance</span>
                        </div>
                    )}
                </div>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            {/* Menu items simples */}
                            {simpleMenuItems.map((item) => (
                                <SidebarMenuItem key={item.path}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isActive(item.path)}
                                        tooltip={item.title}
                                    >
                                        <Link to={item.path}>
                                            <item.icon className="size-4" />
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}

                            {/* Transações com submenus */}
                            <Collapsible
                                open={transacoesOpen}
                                onOpenChange={setTransacoesOpen}
                                className="group/collapsible"
                            >
                                <SidebarMenuItem>
                                    <CollapsibleTrigger asChild>
                                        <SidebarMenuButton
                                            tooltip="Transações"
                                            isActive={isGroupActive(transacoesSubmenus)}
                                        >
                                            <ArrowLeftRight className="size-4" />
                                            <span>Transações</span>
                                            <ChevronDown className={`ml-auto size-4 transition-transform ${transacoesOpen ? 'rotate-180' : ''}`} />
                                        </SidebarMenuButton>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <SidebarMenuSub>
                                            {transacoesSubmenus.map((subItem) => (
                                                <SidebarMenuSubItem key={subItem.path}>
                                                    <SidebarMenuSubButton
                                                        asChild
                                                        isActive={isActive(subItem.path)}
                                                    >
                                                        <Link to={subItem.path}>
                                                            <subItem.icon className="size-4" />
                                                            <span>{subItem.title}</span>
                                                        </Link>
                                                    </SidebarMenuSubButton>
                                                </SidebarMenuSubItem>
                                            ))}
                                        </SidebarMenuSub>
                                    </CollapsibleContent>
                                </SidebarMenuItem>
                            </Collapsible>

                            {/* Itens após transações */}
                            {afterTransacoesItems.map((item) => (
                                <SidebarMenuItem key={item.path}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isActive(item.path)}
                                        tooltip={item.title}
                                    >
                                        <Link to={item.path}>
                                            <item.icon className="size-4" />
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}

                            {/* Admin com submenus */}
                            <Collapsible
                                open={adminOpen}
                                onOpenChange={setAdminOpen}
                                className="group/collapsible"
                            >
                                <SidebarMenuItem>
                                    <CollapsibleTrigger asChild>
                                        <SidebarMenuButton
                                            tooltip="Admin"
                                            isActive={isGroupActive(adminSubmenus)}
                                        >
                                            <Settings className="size-4" />
                                            <span>Admin</span>
                                            <ChevronDown className={`ml-auto size-4 transition-transform ${adminOpen ? 'rotate-180' : ''}`} />
                                        </SidebarMenuButton>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <SidebarMenuSub>
                                            {adminSubmenus.map((subItem) => (
                                                <SidebarMenuSubItem key={subItem.path}>
                                                    <SidebarMenuSubButton
                                                        asChild
                                                        isActive={isActive(subItem.path)}
                                                    >
                                                        <Link to={subItem.path}>
                                                            <subItem.icon className="size-4" />
                                                            <span>{subItem.title}</span>
                                                        </Link>
                                                    </SidebarMenuSubButton>
                                                </SidebarMenuSubItem>
                                            ))}
                                        </SidebarMenuSub>
                                    </CollapsibleContent>
                                </SidebarMenuItem>
                            </Collapsible>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter className="border-t border-border">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <SidebarMenuButton className="cursor-pointer">
                                    <User className="size-4" />
                                    <span className="flex-1 truncate">
                                        {user?.email || 'Usuario'}
                                    </span>
                                    <ChevronUp className="ml-auto size-4" />
                                </SidebarMenuButton>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                side="top"
                                align="start"
                                sideOffset={8}
                                className="w-[200px] z-[9999]"
                            >
                                <DropdownMenuItem
                                    className="cursor-pointer"
                                    onClick={() => logout()}
                                >
                                    Sair
                                </DropdownMenuItem>
                                <DropdownMenuItem disabled className="text-xs text-muted-foreground">
                                    v{APP_VERSION}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>
        </Sidebar>
    );
}
