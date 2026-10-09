        </div> <!-- Fim admin-content -->
    </main>
</div> <!-- Fim admin-wrapper -->

<!-- Scripts -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Controle da Sidebar Retrátil no Celular
    const wrapper = document.getElementById('adminWrapper');
    const menuBtn = document.getElementById('mobileMenuBtn');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const backdrop = document.getElementById('sidebarBackdrop');

    function openSidebar() {
        if (wrapper) wrapper.classList.add('sidebar-open');
        if (backdrop) backdrop.classList.add('active');
        document.body.style.overflow = 'hidden'; // Impede rolagem de fundo
    }

    function closeSidebar() {
        if (wrapper) wrapper.classList.remove('sidebar-open');
        if (backdrop) backdrop.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (menuBtn) menuBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeSidebar();
    });

    // 2. Inicialização do Editor Rico Quill (se existir o container na página)
    const editorContainer = document.querySelector('#editor-container');
    if (editorContainer && !document.querySelector('#form-noticia[data-upload-url]')) {
        const quill = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: 'Escreva a matéria jornalística com clareza, parágrafos bem espaçados e citações...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['link', 'image', 'video'],
                    ['clean']
                ]
            }
        });

        // Sincronização segura no envio do formulário com codificação Base64 (Prevenção de erro 406 ModSecurity)
        const form = document.querySelector('#form-noticia');
        if (form) {
            form.addEventListener('submit', function() {
                const rawHtml = quill.root.innerHTML;
                const conteudoInput = document.querySelector('input[name=conteudo]');
                const b64Input = document.querySelector('input[name=conteudo_b64]');

                if (b64Input) {
                    try {
                        b64Input.value = btoa(unescape(encodeURIComponent(rawHtml)));
                        if (conteudoInput) conteudoInput.value = '';
                    } catch (e) {
                        if (conteudoInput) conteudoInput.value = rawHtml;
                    }
                } else if (conteudoInput) {
                    conteudoInput.value = rawHtml;
                }
            });
        }
    }

    // 3. Pré-visualização instantânea de imagens selecionadas em formulários
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (input.dataset.previewUrl) URL.revokeObjectURL(input.dataset.previewUrl);
                const previewUrl = URL.createObjectURL(file);
                input.dataset.previewUrl = previewUrl;
                let preview = document.querySelector('#image-preview-target');
                if (!preview) {
                    const wrapper = document.querySelector('.image-preview-wrapper');
                    if (wrapper) {
                        preview = document.createElement('img');
                        preview.id = 'image-preview-target';
                        preview.alt = 'Prévia da foto de capa';
                        preview.style.cssText = 'width:100%;max-height:240px;object-fit:cover;border-radius:8px;margin-bottom:12px;display:block';
                        wrapper.replaceChildren(preview);
                    }
                }
                if (preview) preview.src = previewUrl;
            }
        });
    });
});
</script>
<script src="<?= BASE_URL ?>/assets/js/editor-media.js?v=1"></script>
</body>
</html>
