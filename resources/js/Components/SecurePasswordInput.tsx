import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Eye, EyeOff } from 'lucide-react';
import { ChangeEventHandler, ReactNode, useEffect, useRef, useState } from 'react';

type Props = {
    id: string;
    label: string;
    name: string;
    value: string;
    error?: string;
    autoComplete: string;
    isFocused?: boolean;
    labelAction?: ReactNode;
    onChange: (value: string) => void;
};

type RevealedCharacter = {
    character: string;
    index: number;
};

export default function SecurePasswordInput({ id, label, name, value, error, autoComplete, isFocused, labelAction, onChange }: Props) {
    const [showPassword, setShowPassword] = useState(false);
    const [revealedCharacter, setRevealedCharacter] = useState<RevealedCharacter | null>(null);
    const [scrollLeft, setScrollLeft] = useState(0);
    const revealTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    const mask = () => {
        if (revealTimeout.current) clearTimeout(revealTimeout.current);
        setRevealedCharacter(null);
    };

    useEffect(() => {
        const handleVisibility = () => document.hidden && mask();
        document.addEventListener('visibilitychange', handleVisibility);
        return () => {
            document.removeEventListener('visibilitychange', handleVisibility);
            if (revealTimeout.current) clearTimeout(revealTimeout.current);
        };
    }, []);

    const handleChange: ChangeEventHandler<HTMLInputElement> = (event) => {
        const nativeEvent = event.nativeEvent as InputEvent;
        const typedCharacter = nativeEvent.inputType === 'insertText' ? nativeEvent.data : null;
        const input = event.currentTarget;
        const nextValue = input.value;
        onChange(nextValue);
        mask();
        if (!showPassword && typedCharacter?.length === 1) {
            const index = Math.max(0, (input.selectionStart ?? nextValue.length) - typedCharacter.length);
            setRevealedCharacter({ character: typedCharacter, index });
            revealTimeout.current = setTimeout(mask, 500);
        }
        requestAnimationFrame(() => setScrollLeft(input.scrollLeft));
    };

    const maskBefore = revealedCharacter ? '•'.repeat(revealedCharacter.index) : '';
    const maskAfter = revealedCharacter ? '•'.repeat(Math.max(0, value.length - revealedCharacter.index - 1)) : '';

    return (
        <div>
            <div className="flex items-center justify-between">
                <InputLabel className="text-sm font-semibold text-slate-700" htmlFor={id} value={label} />
                {labelAction}
            </div>
            <div className="relative mt-1">
                <TextInput
                    id={id}
                    type={showPassword ? 'text' : 'password'}
                    name={name}
                    value={value}
                    className={`block h-10 w-full rounded-[9px] border-slate-300 pr-12 font-mono text-sm shadow-none focus:border-brand focus:ring-brand ${revealedCharacter && !showPassword ? 'text-transparent caret-slate-900' : ''}`}
                    autoComplete={autoComplete}
                    isFocused={isFocused}
                    onBlur={mask}
                    onChange={handleChange}
                    onScroll={(event) => setScrollLeft(event.currentTarget.scrollLeft)}
                />
                {revealedCharacter && !showPassword && (
                    <span aria-hidden="true" className="pointer-events-none absolute inset-y-0 left-0 right-10 z-10 flex items-center overflow-hidden px-3 font-mono text-sm text-slate-700">
                        <span data-password-reveal data-reveal-index={revealedCharacter.index} className="whitespace-pre" style={{ transform: `translateX(-${scrollLeft}px)` }}>
                            <span>{maskBefore}</span><span data-password-reveal-character>{revealedCharacter.character}</span><span>{maskAfter}</span>
                        </span>
                    </span>
                )}
                <button aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'} className="absolute right-2 top-1/2 z-20 flex size-8 -translate-y-1/2 items-center justify-center rounded text-slate-500 hover:bg-slate-100 hover:text-slate-700" onClick={() => { mask(); setShowPassword((visible) => !visible); }} type="button">
                    {showPassword ? <EyeOff aria-hidden="true" size={18} /> : <Eye aria-hidden="true" size={18} />}
                </button>
            </div>
            <InputError message={error} className="mt-2" />
        </div>
    );
}
