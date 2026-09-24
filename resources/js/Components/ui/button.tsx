import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { ButtonHTMLAttributes, forwardRef } from 'react';
import { cn } from '@/lib/utils';

const buttonVariants = cva(
    'inline-flex h-10 items-center justify-center rounded-[9px] px-4 text-sm font-semibold transition-colors focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50',
    {
        variants: {
            variant: {
                primary: 'bg-brand text-white hover:bg-brand-dark',
                secondary: 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                ghost: 'text-slate-700 hover:bg-slate-100',
                danger: 'bg-red-600 text-white hover:bg-red-700',
            },
        },
        defaultVariants: {
            variant: 'primary',
        },
    },
);

export interface ButtonProps
    extends ButtonHTMLAttributes<HTMLButtonElement>, VariantProps<typeof buttonVariants> {
    asChild?: boolean;
}

const Button = forwardRef<HTMLButtonElement, ButtonProps>(
    ({ className, variant, asChild = false, ...props }, ref) => {
        const Component = asChild ? Slot : 'button';

        return <Component className={cn(buttonVariants({ variant, className }))} ref={ref} {...props} />;
    },
);

Button.displayName = 'Button';

export { Button, buttonVariants };
