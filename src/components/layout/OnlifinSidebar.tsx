import * as React from 'react';
import { Link, useLocation } from 'react-router-dom';
import {
    Home,
    ArrowLeftRight,
    Building2,
    CreditCard,
    Settings,
    ChevronUp,
    ChevronDown,
    User,
    FileText,
    RefreshCw,
    Receipt,
    DollarSign,
    Users,
    Bot,
    Sliders,
    Layers,
    LogOut,
    Plus,
    Check,
    ChevronRight
} from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
import { Button } from '@/components/ui/button';
import { useAuth } from 'miaoda-auth-react';
import { useCompany } from '@/contexts/CompanyContext';
import { APP_VERSION } from '@/config/version';

// Configuração comum de submenus para Transações
const TRANSACTIONS_SUBMENU = [
    { title: 'Geral', icon: ArrowLeftRight, subPath: '/transactions' },
    { title: 'Contas a Pagar', icon: Receipt, subPath: '/bills-to-pay' },
    { title: 'Contas a Receber', icon: DollarSign, subPath: '/bills-to-receive' },
    { title: 'Importar Extrato', icon: FileText, subPath: '/import-statements' },
    { title: 'Conciliação', icon: RefreshCw, subPath: '/reconciliation' },
];

// Menu Pessoa Física (PF)
const PF_MENU = [
    { title: 'Dashboard', icon: Home, path: '/pf' },
    { title: 'Pessoas', icon: Users, path: '/pf/people' },
    { title: 'Contas', icon: Building2, path: '/pf/accounts' },
    { title: 'Cartões', icon: CreditCard, path: '/pf/cards' },
    {
        title: 'Transações',
        icon: ArrowLeftRight,
        basePath: '/pf',
        subItems: TRANSACTIONS_SUBMENU
    },
];

// Menu Pessoa Jurídica (PJ) - Base
const PJ_MENU_BASE = (companyId: string) => [
    { title: 'Dashboard', icon: Home, path: `/pj/${companyId}` },
    { title: 'Contas', icon: Building2, path: `/pj/${companyId}/accounts` },
    { title: 'Cartões', icon: CreditCard, path: `/pj/${companyId}/cards` },
    {
        title: 'Transações',
        icon: ArrowLeftRight,
        basePath: `/pj/${companyId}`,
        subItems: TRANSACTIONS_SUBMENU
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
    const navigate = useNavigate();
    const { user, logout } = useAuth();
    const { state } = useSidebar();
    const { companies, selectedCompany, selectCompany } = useCompany();
    const [openMenus, setOpenMenus] = React.useState<Record<string, boolean>>({});
    const [userMenuOpen, setUserMenuOpen] = React.useState(false);
    const [pfOpen, setPfOpen] = React.useState(true);
    const [pjOpen, setPjOpen] = React.useState(true);

    // Abrir menus automaticamente com base na rota
    React.useEffect(() => {
        const currentPath = location.pathname;
        const newOpenMenus = { ...openMenus };

        if (currentPath.includes('/transactions') ||
            currentPath.includes('/bills-') ||
            currentPath.includes('/import-statements') ||
            currentPath.includes('/reconciliation')) {
            if (currentPath.startsWith('/pf')) newOpenMenus['pf-transactions'] = true;
            if (currentPath.startsWith('/pj')) newOpenMenus['pj-transactions'] = true;
        }

        if (adminSubmenus.some(item => currentPath === item.path)) {
            newOpenMenus['admin'] = true;
        }

        setOpenMenus(newOpenMenus);
    }, [location.pathname]);

    const toggleMenu = (key: string) => {
        setOpenMenus(prev => ({ ...prev, [key]: !prev[key] }));
    };

    const isActive = (path: string) => location.pathname === path;

    const handleCompanyChange = (companyId: string) => {
        selectCompany(companyId);
        // Atualizar rota se estiver em uma página PJ
        if (location.pathname.startsWith('/pj/')) {
            const pathParts = location.pathname.split('/');
            const restOfPath = pathParts.slice(3).join('/');
            navigate(`/pj/${companyId}${restOfPath ? `/${restOfPath}` : ''}`);
        }
    };

    const renderMenuItem = (item: any, basePath: string = '') => {
        const fullPath = item.path || `${basePath}${item.subPath}`;

        if (item.subItems) {
            const menuKey = `${basePath.replace(/\//g, '-')}-${item.title.toLowerCase()}`;
            const isOpen = openMenus[menuKey];
            const isAnySubActive = item.subItems.some((sub: any) => isActive(`${item.basePath}${sub.subPath}`));

            return (
                <Collapsible
                    key={menuKey}
                    open={isOpen}
                    onOpenChange={() => toggleMenu(menuKey)}
                    className="group/collapsible"
                >
                    <SidebarMenuItem>
                        <CollapsibleTrigger asChild>
                            <SidebarMenuButton
                                tooltip={item.title}
                                isActive={isAnySubActive}
                            >
                                <item.icon className="size-4" />
                                <span>{item.title}</span>
                                <ChevronDown className={`ml-auto size-4 transition-transform ${isOpen ? 'rotate-180' : ''}`} />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                {item.subItems.map((subItem: any) => (
                                    <SidebarMenuSubItem key={subItem.subPath}>
                                        <SidebarMenuSubButton
                                            asChild
                                            isActive={isActive(`${item.basePath}${subItem.subPath}`)}
                                        >
                                            <Link to={`${item.basePath}${subItem.subPath}`}>
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
            );
        }

        return (
            <SidebarMenuItem key={fullPath}>
                <SidebarMenuButton
                    asChild
                    isActive={isActive(fullPath)}
                    tooltip={item.title}
                >
                    <Link to={fullPath}>
                        <item.icon className="size-4" />
                        <span>{item.title}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        );
    };

    return (
        <Sidebar collapsible="icon" className="border-r border-border">
            <SidebarHeader className="border-b border-border p-4">
                <div className="flex items-center gap-2">
                    <div className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <span className="text-lg font-bold">
                            {selectedCompany?.nome_fantasia?.[0] || selectedCompany?.razao_social?.[0] || 'O'}
                        </span>
                    </div>
                    {state === 'expanded' && (
                        <div className="flex flex-col">
                            <span className="text-sm font-bold text-foreground truncate max-w-[150px]">
                                {location.pathname.startsWith('/pj') && selectedCompany
                                    ? (selectedCompany.nome_fantasia || selectedCompany.razao_social)
                                    : 'Onlifin'}
                            </span>
                            <span className="text-[10px] text-muted-foreground">
                                {location.pathname.startsWith('/pj') && selectedCompany
                                    ? 'Pessoa Jurídica'
                                    : 'Personal & Business'}
                            </span>
                        </div>
                    )}
                </div>
            </SidebarHeader>

            <SidebarContent>
                {/* PESSOA FÍSICA */}
                <Collapsible open={pfOpen} onOpenChange={setPfOpen}>
                    <SidebarGroup>
                        <CollapsibleTrigger asChild>
                            <div className="px-2 py-2 flex items-center justify-between cursor-pointer group">
                                <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider px-2">
                                    Pessoa Física
                                </span>
                                {state === 'expanded' && (
                                    <ChevronDown className={`size-3 text-muted-foreground transition-transform ${pfOpen ? 'rotate-180' : ''}`} />
                                )}
                            </div>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarGroupContent>
                                <SidebarMenu>
                                    {PF_MENU.map(item => renderMenuItem(item))}
                                </SidebarMenu>
                            </SidebarGroupContent>
                        </CollapsibleContent>
                    </SidebarGroup>
                </Collapsible>

                {/* PESSOA JURÍDICA */}
                <Collapsible open={pjOpen} onOpenChange={setPjOpen}>
                    <SidebarGroup>
                        <div className="px-2 py-2 flex items-center justify-between">
                            <CollapsibleTrigger asChild>
                                <div className="flex items-center gap-1 cursor-pointer group">
                                    <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider px-2">
                                        Pessoa Jurídica
                                    </span>
                                    {state === 'expanded' && (
                                        <ChevronDown className={`size-3 text-muted-foreground transition-transform ${pjOpen ? 'rotate-180' : ''}`} />
                                    )}
                                </div>
                            </CollapsibleTrigger>
                            {state === 'expanded' && (
                                <Link to="/companies" className="text-[10px] text-primary hover:underline px-2">
                                    Gerenciar
                                </Link>
                            )}
                        </div>
                        <CollapsibleContent>
                            <SidebarGroupContent>
                                <SidebarMenu>
                                    {companies.length > 0 ? (
                                        <>
                                            {/* Seletor de Empresa na Sidebar */}
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <div className="px-3 py-3 mb-4 bg-primary text-primary-foreground rounded-lg mx-2 cursor-pointer transition-all hover:brightness-110 shadow-md group">
                                                        <div className="flex items-center justify-between">
                                                            <div className="flex-1 min-w-0">
                                                                <p className="text-xs font-black uppercase tracking-tight truncate">
                                                                    {selectedCompany?.nome_fantasia || selectedCompany?.razao_social || 'Selecionar Empresa'}
                                                                </p>
                                                                {selectedCompany && (
                                                                    <p className="text-[10px] opacity-80 truncate">{selectedCompany.cnpj}</p>
                                                                )}
                                                            </div>
                                                            <ChevronRight className="size-4 opacity-80 group-hover:opacity-100 transition-all ml-2" />
                                                        </div>
                                                    </div>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="start" className="w-[240px] z-[9999]">
                                                    <DropdownMenuLabel className="text-xs">Minhas Empresas</DropdownMenuLabel>
                                                    <DropdownMenuSeparator />
                                                    {companies.map(company => (
                                                        <DropdownMenuItem
                                                            key={company.id}
                                                            onClick={() => handleCompanyChange(company.id)}
                                                            className="flex items-center justify-between text-xs cursor-pointer"
                                                        >
                                                            <div className="flex flex-col truncate pr-2">
                                                                <span className="font-medium truncate">{company.nome_fantasia || company.razao_social}</span>
                                                                <span className="text-[10px] text-muted-foreground">{company.cnpj}</span>
                                                            </div>
                                                            {selectedCompany?.id === company.id && (
                                                                <Check className="size-3 text-primary ml-auto" />
                                                            )}
                                                        </DropdownMenuItem>
                                                    ))}
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem asChild>
                                                        <Link to="/companies" className="flex items-center gap-2 cursor-pointer text-xs">
                                                            <Plus className="size-3" />
                                                            Adicionar Empresa
                                                        </Link>
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>

                                            {selectedCompany && PJ_MENU_BASE(selectedCompany.id).map(item => renderMenuItem(item))}
                                        </>
                                    ) : (
                                        <SidebarMenuItem>
                                            <SidebarMenuButton asChild tooltip="Selecionar Empresa">
                                                <Link to="/companies">
                                                    <Building2 className="size-4" />
                                                    <span>Selecionar Empresa</span>
                                                </Link>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    )}
                                </SidebarMenu>
                            </SidebarGroupContent>
                        </CollapsibleContent>
                    </SidebarGroup>
                </Collapsible>

                {/* ADMIN */}
                <SidebarGroup className="mt-auto">
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <Collapsible
                                open={openMenus['admin']}
                                onOpenChange={() => toggleMenu('admin')}
                                className="group/collapsible"
                            >
                                <SidebarMenuItem>
                                    <CollapsibleTrigger asChild>
                                        <SidebarMenuButton
                                            tooltip="Ajustes e IA"
                                            isActive={adminSubmenus.some(item => isActive(item.path))}
                                        >
                                            <Settings className="size-4" />
                                            <span>Ajustes e IA</span>
                                            <ChevronDown className={`ml-auto size-4 transition-transform ${openMenus['admin'] ? 'rotate-180' : ''}`} />
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

            <SidebarFooter className="border-t border-border relative">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            className="cursor-pointer w-full"
                            onClick={() => setUserMenuOpen(!userMenuOpen)}
                        >
                            <User className="size-4" />
                            <span className="flex-1 truncate text-left">
                                {user?.email || 'Usuário'}
                            </span>
                            {userMenuOpen ? (
                                <ChevronDown className="ml-auto size-4" />
                            ) : (
                                <ChevronUp className="ml-auto size-4" />
                            )}
                        </SidebarMenuButton>

                        {userMenuOpen && (
                            <div className="absolute bottom-full left-0 right-0 mb-2 mx-2 bg-popover border border-border rounded-lg shadow-lg p-2 z-[9999]">
                                <Link
                                    to="/user-management"
                                    onClick={() => setUserMenuOpen(false)}
                                >
                                    <Button
                                        variant="ghost"
                                        className="w-full justify-start gap-2"
                                    >
                                        <User className="size-4" />
                                        Perfil
                                    </Button>
                                </Link>
                                <Button
                                    variant="ghost"
                                    className="w-full justify-start gap-2 text-destructive hover:text-destructive hover:bg-destructive/10"
                                    onClick={() => {
                                        setUserMenuOpen(false);
                                        logout();
                                    }}
                                >
                                    <LogOut className="size-4" />
                                    Sair
                                </Button>
                                <div className="text-xs text-muted-foreground text-center pt-2 border-t mt-1">
                                    v{APP_VERSION}
                                </div>
                            </div>
                        )}
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>
        </Sidebar>
    );
}
