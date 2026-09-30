import { useEditor, EditorContent } from '@tiptap/react';
import type { Editor as TiptapEditor, JSONContent } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { useEffect, useRef } from 'react';
import EditorToolbar from '@/components/documents/editor-toolbar';

type Props = {
    content: JSONContent;
    editable: boolean;
    onChange: () => void;
    onEditorReady: (editor: TiptapEditor | null) => void;
};

export default function RichEditor({
    content,
    editable,
    onChange,
    onEditorReady,
}: Props) {
    const editorInitialized = useRef(false);
    const editor = useEditor({
        extensions: [
            StarterKit.configure({
                heading: { levels: [1, 2, 3] },
                link: false,
            }),
        ],
        content,
        editable,
        immediatelyRender: false,
        onCreate: () => {
            editorInitialized.current = true;
        },
        onUpdate: () => {
            if (editorInitialized.current) {
                onChange();
            }
        },
    });

    useEffect(() => {
        onEditorReady(editor);

        return () => onEditorReady(null);
    }, [editor, onEditorReady]);

    return (
        <div className="space-y-3">
            <EditorToolbar editor={editor} editable={editable} />
            <EditorContent
                editor={editor}
                className="min-h-96 rounded-md border bg-background px-6 py-5"
            />
        </div>
    );
}
