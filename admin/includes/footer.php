        </div> <!-- Fim admin-content -->
    </main>
</div> <!-- Fim admin-wrapper -->

<!-- Scripts -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicialização do Quill Editor se existir o container
    var editorContainer = document.querySelector('#editor-container');
    if (editorContainer) {
        var quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'font': [] }],
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    [{ 'align': [] }],
                    ['link', 'image', 'video'],
                    ['clean']
                ]
            }
        });

        // Sincronizar o conteúdo do Quill com um input hidden no submit do form
        var form = document.querySelector('#form-noticia');
        if (form) {
            form.onsubmit = function() {
                var conteudoInput = document.querySelector('input[name=conteudo]');
                conteudoInput.value = quill.root.innerHTML;
            };
        }
    }
});
</script>
</body>
</html>
