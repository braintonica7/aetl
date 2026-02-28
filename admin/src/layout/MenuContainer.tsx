import React, { useState } from 'react';
import {
    DashboardMenuItem,
    MenuItemLink,
} from 'react-admin';
import SubMenu from './SubMenu';

const MenuIcon = ({menuStr}) => {
    const i = "<i class='fas fa-ambulance'></i>";
    return <span dangerouslySetInnerHTML={{
        __html: menuStr
      }}></span>
}

export const MenuContainer = (props) => {
    const [isOpen, setIsOpen] = useState(false);
    const { onMenuClick, sidebarIsOpen, dense, caption, items } = props;

    //console.log(caption);
    //console.log(items)
    let catIcon = '<i class="fa fa-transgender-alt"></i>';
    if(items.length > 0){
        catIcon = items[0].CatIcon;
    }
    const handleToggle = () => {
        setIsOpen(!isOpen);
    };
    return <SubMenu
        handleToggle={() => handleToggle()}
        isOpen={isOpen}
        name={caption}
        dense={dense}
        icon={<MenuIcon menuStr={catIcon} />}
    >
        {items && items.map((item, index) => {
            if (item.View == 1) {
                //console.log(item);
                return (
                    <MenuItemLink key={index}
                        to={item.NavigateUrl}
                        primaryText={item.Module}
                        leftIcon={<MenuIcon menuStr={item.Icon} />}
                        onClick={onMenuClick}
                        dense={dense}
                        sx={{
                            '& .MuiTypography-root': {
                                whiteSpace: 'normal',
                                wordBreak: 'break-word',
                                lineHeight: 1.3,
                                fontSize: '0.875rem'
                            }
                        }}
                    />
                )
            } else {
                return null
            }

        })}
    </SubMenu>;
}
export default MenuContainer;