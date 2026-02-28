import React, { FC, useState, useEffect } from 'react';
import { Box, Divider } from '@mui/material';
import LabelIcon from '@mui/icons-material/Label';
import VpnKeyIcon from '@mui/icons-material/VpnKey';
import PowerSettingsNewIcon from '@mui/icons-material/PowerSettingsNew';

import {
    useTranslate,
    DashboardMenuItem,
    MenuItemLink,
    MenuProps,
    useSidebarState,
    useLogout
} from 'react-admin';

import SubMenu from './SubMenu';
import MenuContainer from './MenuContainer';
import menuitems from './menuitems.json';
import { MenuItem } from '../types';

type MenuName = 'menuCatalog' | 'menuSales' | 'menuCustomers';

const Menu = ({ dense = false }: MenuProps) => {
    const [state, setState] = useState({
        menuCatalog: true,
        menuSales: true,
        menuCustomers: true,
    });
    const [rights, setRights] = useState([]);
    const [categories, setCategories] = useState<string[]>([]);

    const translate = useTranslate();
    const [open, setOpen] = useSidebarState();
    const logout = useLogout();
    
    /**
     * SetMenuData - Filters menu items based on user role
     * Role-based access control:
     * - role_id = 1 (Admin): Access to all menu items
     * - role_id != 1 (Other roles): Access only to Question, Chapter, and Topic
     */
    const SetMenuData = () => {
        let menuData: MenuItem[] = [];
        let menu = localStorage.getItem("menu");
        if (menu) {
            menuData = JSON.parse(menu);
        }
        menuData = menuitems as MenuItem[];

        // Get user's role from localStorage
        const userRoleId = localStorage.getItem("role_id");
        const roleId = userRoleId ? parseInt(userRoleId) : null;

        console.log(`User role_id: ${roleId}, Menu filtering active: ${roleId !== 1}`);

        // Filter menu items based on role
        if (roleId !== 1) {
            // For non-admin users (role_id != 1), only show Question, Chapter, and Topic
            const allowedModules = ['Question', 'Chapter', 'Topic'];
            menuData = menuData.filter((item: MenuItem) => 
                allowedModules.includes(item.Module)
            );
            console.log(`Filtered menu items for non-admin user. Showing ${menuData.length} items.`);
        } else {
            console.log(`Admin user detected. Showing all ${menuData.length} menu items.`);
        }
        // For role_id = 1 (admin), show all menu items (no filtering needed)

        localStorage.setItem("menu", JSON.stringify(menuData));
        var outObject = menuData.reduce(function (a:any, e:MenuItem) {
            let estKey = (e['ModuleCategory']);
            if(a[estKey] == undefined)
                a[estKey] = [];
            a[estKey].push(e);
            return a;
        }, {});
        var keys = Object.keys(outObject);
        setCategories(keys);
        setRights(outObject);
    }

    useEffect(() => {
        SetMenuData();
    },[]);

    const onMenuClick = () => {
        console.log("Menu click......");
    }
    const handleToggle = (menu: any) => {
        setState(state => ({ ...state, [menu]: !state[menu] }));
    };

    return (
        <Box
            sx={{
                width: open ? 260 : 50,
                marginTop: 1,
                marginBottom: 1,
                transition: theme =>
                    theme.transitions.create('width', {
                        easing: theme.transitions.easing.sharp,
                        duration: theme.transitions.duration.leavingScreen,
                    }),
            }}
        >
           {' '}
            <DashboardMenuItem onClick={onMenuClick} />
            <Divider />
            {categories && categories.map((item:any,index:number) =>{
                return(
                    <MenuContainer key={index} onMenuClick={onMenuClick} sidebarIsOpen={open} dense={dense} caption={item} items={rights[item]} />
                )
            })}
            <Divider />
        </Box>
    );
};

export default Menu;
