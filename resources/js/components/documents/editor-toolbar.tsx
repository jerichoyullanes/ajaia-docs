import type { Editor } from '@tiptap/react';
import {
    Bold,
    Heading1,
    Heading2,
    Italic,
    List,
    ListOrdered,
    Pilcrow,
    Redo,
    Underline,
    Undo,
} from 'lucide-react';
import { useEditorState } from '@tiptap/react';
import { Button } from '@/components/ui/button';

type Props = {
    editor: Editor | null;
    editable: boolean;
};

export default function EditorToolbar({ editor, editable }: Props) {
    const active = useEditorState({
        editor,
        selector: ({ editor: currentEditor }) => ({
            bold: currentEditor?.isActive('bold') ?? false,
            italic: currentEditor?.isActive('italic') ?? false,
            underline: currentEditor?.isActive('underline') ?? false,
            heading1: currentEditor?.isActive('heading', { level: 1 }) ?? false,
            heading2: currentEditor?.isActive('heading', { level: 2 }) ?? false,
            paragraph: currentEditor?.isActive('paragraph') ?? false,
            bulletList: currentEditor?.isActive('bulletList') ?? false,
            orderedList: currentEditor?.isActive('orderedList') ?? false,
            canUndo: currentEditor?.can().undo() ?? false,
            canRedo: currentEditor?.can().redo() ?? false,
        }),
    });

    const disabled = !editor || !editable;

    return (
        <div
            className="flex flex-wrap items-center gap-1 rounded-md border p-2"
            role="toolbar"
            aria-label="Text formatting"
        >
            <Button
                type="button"
                variant={active?.bold ? 'secondary' : 'ghost'}
                size="icon"
                aria-label="Bold"
                aria-pressed={active?.bold ?? false}
                disabled={disabled}
                onMouseDown={(event) => event.preventDefault()}
                onClick={() => editor?.chain().focus().toggleBold().run()}
            >
                <Bold />
            </Button>
            <Button
                type="button"
                variant={active?.italic ? 'secondary' : 'ghost'}
                size="icon"
                aria-label="Italic"
                aria-pressed={active?.italic ?? false}
                disabled={disabled}
                onMouseDown={(event) => event.preventDefault()}
                onClick={() => editor?.chain().focus().toggleItalic().run()}
            >
                <Italic />
            </Button>
            <Button
                type="button"
                variant={active?.underline ? 'secondary' : 'ghost'}
                size="icon"
                aria-label="Underline"
                aria-pressed={active?.underline ?? false}
                disabled={disabled}
                onMouseDown={(event) => event.preventDefault()}
                onClick={() => editor?.chain().focus().toggleUnderline().run()}
            >
                <Underline />
            </Button>
            <Button
                type="button"
                variant={active?.heading1 ? 'secondary' : 'ghost'}
                size="icon"
                aria-label="Heading 1"
                aria-pressed={active?.heading1 ?? false}
                disabled={disabled}
                onMouseDown={(event) => event.preventDefault()}
                onClick={() =>
                    editor?.chain().focus().toggleHeading({ level: 1 }).run()
                }
            >
                <Heading1 />
            </Button>
            <Button
                type="button"
                variant={active?.heading2 ? 'secondary' : 'ghost'}
                size="icon"
                aria-label="Heading 2"
                aria-pressed={active?.heading2 ?? false}
                disabled={disabled}
                onMouseDown={(event) => event.preventDefault()}
                onClick={() =>
                    editor?.chain().focus().toggleHeading({ level: 2 }).run()
                }
            >
                <Heading2 />
            </Button>
            <Button
                type="button"
                variant={active?.paragraph ? 'secondary' : 'ghost'}
                size="icon"
                aria-label="Paragraph"
                aria-pressed={active?.paragraph ?? false}
                disabled={disabled}
                onMouseDown={(event) => event.preventDefault()}
                onClick={() => editor?.chain().focus().setParagraph().run()}
            >
                <Pilcrow />
            </Button>
            <Button
                type="button"
                variant={active?.bulletList ? 'secondary' : 'ghost'}
                size="icon"
                aria-label="Bullet list"
                aria-pressed={active?.bulletList ?? false}
                disabled={disabled}
                onMouseDown={(event) => event.preventDefault()}
                onClick={() => editor?.chain().focus().toggleBulletList().run()}
            >
                <List />
            </Button>
            <Button
                type="button"
                variant={active?.orderedList ? 'secondary' : 'ghost'}
                size="icon"
                aria-label="Ordered list"
                aria-pressed={active?.orderedList ?? false}
                disabled={disabled}
                onMouseDown={(event) => event.preventDefault()}
                onClick={() =>
                    editor?.chain().focus().toggleOrderedList().run()
                }
            >
                <ListOrdered />
            </Button>
            <span className="mx-1 h-6 border-l" aria-hidden="true" />
            <Button
                type="button"
                variant="ghost"
                size="icon"
                aria-label="Undo"
                disabled={disabled || !active?.canUndo}
                onMouseDown={(event) => event.preventDefault()}
                onClick={() => editor?.chain().focus().undo().run()}
            >
                <Undo />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                aria-label="Redo"
                disabled={disabled || !active?.canRedo}
                onMouseDown={(event) => event.preventDefault()}
                onClick={() => editor?.chain().focus().redo().run()}
            >
                <Redo />
            </Button>
        </div>
    );
}
