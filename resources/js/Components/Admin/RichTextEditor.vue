<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import TextAlign from '@tiptap/extension-text-align';
import Link from '@tiptap/extension-link';
import Underline from '@tiptap/extension-underline';
import Placeholder from '@tiptap/extension-placeholder';
import Image from '@tiptap/extension-image';
import { watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    dir: { type: String, default: 'ltr' },
    placeholder: { type: String, default: 'Start writing...' },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3, 4] },
        }),
        Underline,
        TextAlign.configure({
            types: ['heading', 'paragraph'],
            alignments: ['left', 'center', 'right'],
        }),
        Link.configure({
            openOnClick: false,
            HTMLAttributes: { class: 'text-blue-600 underline' },
        }),
        Image.configure({
            inline: true,
            HTMLAttributes: { class: 'max-w-full rounded-lg' },
        }),
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
    ],
    editorProps: {
        attributes: {
            dir: props.dir,
            class: 'prose prose-sm max-w-none focus:outline-none min-h-[200px] px-4 py-3',
        },
    },
    onUpdate: () => {
        emit('update:modelValue', editor.value.getHTML());
    },
});

watch(() => props.modelValue, (value) => {
    if (editor.value && editor.value.getHTML() !== value) {
        editor.value.commands.setContent(value || '', false);
    }
});

function setLink() {
    const previousUrl = editor.value.getAttributes('link').href;
    const url = window.prompt('URL', previousUrl);
    if (url === null) return;
    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}

function addImage() {
    const url = window.prompt('Image URL');
    if (url) {
        editor.value.chain().focus().setImage({ src: url }).run();
    }
}
</script>

<template>
    <div class="border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-yellow-200 focus-within:border-transparent">
        <!-- Toolbar -->
        <div v-if="editor" class="flex flex-wrap items-center gap-0.5 px-2 py-1.5 bg-gray-50 border-b border-gray-200">
            <!-- Text Style -->
            <button type="button" @click="editor.chain().focus().toggleBold().run()" :class="editor.isActive('bold') ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="p-1.5 rounded text-sm font-bold" title="Bold">
                B
            </button>
            <button type="button" @click="editor.chain().focus().toggleItalic().run()" :class="editor.isActive('italic') ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="p-1.5 rounded text-sm italic" title="Italic">
                I
            </button>
            <button type="button" @click="editor.chain().focus().toggleUnderline().run()" :class="editor.isActive('underline') ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="p-1.5 rounded text-sm underline" title="Underline">
                U
            </button>

            <div class="w-px h-5 bg-gray-300 mx-1"></div>

            <!-- Headings -->
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :class="editor.isActive('heading', { level: 2 }) ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="p-1.5 rounded text-xs font-bold" title="Heading 2">
                H2
            </button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" :class="editor.isActive('heading', { level: 3 }) ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="p-1.5 rounded text-xs font-bold" title="Heading 3">
                H3
            </button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 4 }).run()" :class="editor.isActive('heading', { level: 4 }) ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="p-1.5 rounded text-xs font-bold" title="Heading 4">
                H4
            </button>

            <div class="w-px h-5 bg-gray-300 mx-1"></div>

            <!-- Lists -->
            <button type="button" @click="editor.chain().focus().toggleBulletList().run()" :class="editor.isActive('bulletList') ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="p-1.5 rounded" title="Bullet List">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleOrderedList().run()" :class="editor.isActive('orderedList') ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="p-1.5 rounded" title="Ordered List">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20h14M7 12h14M7 4h14M3 20h.01M3 12h.01M3 4h.01" /></svg>
            </button>

            <div class="w-px h-5 bg-gray-300 mx-1"></div>

            <!-- Alignment -->
            <button type="button" @click="editor.chain().focus().setTextAlign('left').run()" :class="editor.isActive({ textAlign: 'left' }) ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="p-1.5 rounded" title="Align Left">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h14" /></svg>
            </button>
            <button type="button" @click="editor.chain().focus().setTextAlign('center').run()" :class="editor.isActive({ textAlign: 'center' }) ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="p-1.5 rounded" title="Align Center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M5 18h14" /></svg>
            </button>
            <button type="button" @click="editor.chain().focus().setTextAlign('right').run()" :class="editor.isActive({ textAlign: 'right' }) ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="p-1.5 rounded" title="Align Right">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M6 18h14" /></svg>
            </button>

            <div class="w-px h-5 bg-gray-300 mx-1"></div>

            <!-- Link & Image -->
            <button type="button" @click="setLink" :class="editor.isActive('link') ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="p-1.5 rounded" title="Link">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
            </button>
            <button type="button" @click="addImage" class="p-1.5 rounded text-gray-600 hover:bg-gray-100" title="Image">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </button>

            <div class="w-px h-5 bg-gray-300 mx-1"></div>

            <!-- Blockquote & HR -->
            <button type="button" @click="editor.chain().focus().toggleBlockquote().run()" :class="editor.isActive('blockquote') ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="p-1.5 rounded text-sm" title="Blockquote">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
            </button>
            <button type="button" @click="editor.chain().focus().setHorizontalRule().run()" class="p-1.5 rounded text-gray-600 hover:bg-gray-100" title="Horizontal Rule">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16" /></svg>
            </button>

            <div class="flex-1"></div>

            <!-- Undo / Redo -->
            <button type="button" @click="editor.chain().focus().undo().run()" :disabled="!editor.can().undo()" class="p-1.5 rounded text-gray-600 hover:bg-gray-100 disabled:opacity-30" title="Undo">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a5 5 0 015 5v2M3 10l4-4m-4 4l4 4" /></svg>
            </button>
            <button type="button" @click="editor.chain().focus().redo().run()" :disabled="!editor.can().redo()" class="p-1.5 rounded text-gray-600 hover:bg-gray-100 disabled:opacity-30" title="Redo">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a5 5 0 00-5 5v2m15-7l-4-4m4 4l-4 4" /></svg>
            </button>
        </div>

        <!-- Editor Content -->
        <EditorContent :editor="editor" />
    </div>
</template>

<style>
.tiptap {
    min-height: 200px;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    line-height: 1.6;
}
.tiptap:focus {
    outline: none;
}
.tiptap p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #9ca3af;
    pointer-events: none;
    height: 0;
}
.tiptap h2 { font-size: 1.25rem; font-weight: 700; margin: 1rem 0 0.5rem; }
.tiptap h3 { font-size: 1.1rem; font-weight: 600; margin: 0.75rem 0 0.5rem; }
.tiptap h4 { font-size: 1rem; font-weight: 600; margin: 0.5rem 0 0.25rem; }
.tiptap p { margin: 0.25rem 0; }
.tiptap ul { list-style: disc; padding-left: 1.5rem; margin: 0.5rem 0; }
.tiptap ol { list-style: decimal; padding-left: 1.5rem; margin: 0.5rem 0; }
.tiptap li { margin: 0.125rem 0; }
.tiptap blockquote { border-left: 3px solid #C4A265; padding-left: 1rem; margin: 0.5rem 0; color: #6b7280; font-style: italic; }
.tiptap hr { border-color: #e5e7eb; margin: 1rem 0; }
.tiptap a { color: #2563eb; text-decoration: underline; }
.tiptap img { max-width: 100%; border-radius: 0.5rem; margin: 0.5rem 0; }
</style>
