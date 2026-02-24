// Funções JavaScript principais para o sistema de denúncias

document.addEventListener('DOMContentLoaded', function() {
    // Adicionar máscara para campos de data de validade para garantir formato adequado
    const expirationDateInputs = document.querySelectorAll('input[type="date"]');
    expirationDateInputs.forEach(input => {
        // Garantir que a data mínima seja hoje
        const today = new Date().toISOString().split('T')[0];
        if (!input.min) {
            input.min = today;
        }
    });

    // Função para pré-visualizar imagem selecionada
    const imageInput = document.getElementById('image');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Verificar tamanho do arquivo (5MB)
                if (file.size > 5000000) {
                    alert('A imagem selecionada é muito grande. O tamanho máximo permitido é 5MB.');
                    e.target.value = '';
                    return;
                }

                // Verificar tipo de arquivo
                if (!file.type.match('image.*')) {
                    alert('Por favor, selecione um arquivo de imagem válido.');
                    e.target.value = '';
                    return;
                }

                // Mostrar mensagem sobre geolocalização
                console.log('A geolocalização será extraída automaticamente da imagem para verificação de veracidade.');
            }
        });
    }

    // Função para lidar com o envio de formulário de denúncia
    const reportForm = document.querySelector('form[method="POST"]');
    if (reportForm && reportForm.action.includes('report.php')) {
        reportForm.addEventListener('submit', function(e) {
            // Verificar se a imagem foi selecionada
            const imageField = document.getElementById('image');
            if (imageField && !imageField.value) {
                alert('Por favor, selecione uma imagem do produto vencido.');
                e.preventDefault();
                return;
            }

            // Mostrar mensagem de confirmação
            if (!confirm('Tem certeza de que deseja enviar esta denúncia? As informações serão verificadas pela equipe responsável.')) {
                e.preventDefault();
            }
        });
    }

    // Função para carregar dados de geolocalização (quando disponível)
    loadGeolocationData();
});

// Função para carregar e exibir dados de geolocalização
function loadGeolocationData() {
    // Esta função pode ser expandida para interagir com mapas ou mostrar coordenadas
    console.log('Sistema de extração de geolocalização inicializado.');
}

// Função para validar formulários
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    });

    return isValid;
}