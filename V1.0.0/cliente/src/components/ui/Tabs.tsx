import { useRef, type KeyboardEvent, type ReactNode } from 'react';
import { cn } from '../../lib/cn';

export interface TabItem {
  id: string;
  label: string;
  icon?: ReactNode;
  badge?: ReactNode;
  permission?: string;
}

interface TabsProps {
  tabs: TabItem[];
  active: string;
  onChange: (id: string) => void;
  orientation?: 'horizontal' | 'vertical';
  ariaLabel: string;
  panelId?: string;
}

export function Tabs({ tabs, active, onChange, orientation = 'horizontal', ariaLabel, panelId }: TabsProps) {
  const refs = useRef<Record<string, HTMLButtonElement | null>>({});

  const onKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
    const currentIndex = tabs.findIndex((tab) => tab.id === active);
    if (currentIndex < 0) return;
    const isHorizontal = orientation === 'horizontal';
    const next = isHorizontal ? 'ArrowRight' : 'ArrowDown';
    const previous = isHorizontal ? 'ArrowLeft' : 'ArrowUp';
    let index = currentIndex;

    if (event.key === next) index = (currentIndex + 1) % tabs.length;
    else if (event.key === previous) index = (currentIndex - 1 + tabs.length) % tabs.length;
    else if (event.key === 'Home') index = 0;
    else if (event.key === 'End') index = tabs.length - 1;
    else return;

    event.preventDefault();
    const target = tabs[index];
    onChange(target.id);
    refs.current[target.id]?.focus();
  };

  return (
    <div
      className={cn('tabs', `tabs--${orientation}`)}
      role="tablist"
      aria-label={ariaLabel}
      aria-orientation={orientation}
      onKeyDown={onKeyDown}
    >
      {tabs.map((tab) => {
        const selected = tab.id === active;
        return (
          <button
            key={tab.id}
            ref={(element) => {
              refs.current[tab.id] = element;
            }}
            type="button"
            role="tab"
            id={`tab-${tab.id}`}
            aria-selected={selected}
            aria-controls={panelId ? `${panelId}-${tab.id}` : undefined}
            tabIndex={selected ? 0 : -1}
            className={cn('tabs__tab', selected && 'is-active')}
            onClick={() => onChange(tab.id)}
          >
            {tab.icon}
            <span>{tab.label}</span>
            {tab.badge}
          </button>
        );
      })}
    </div>
  );
}
