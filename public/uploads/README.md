# 📁 Diretório de Uploads

Este diretório contém todos os arquivos enviados pelos usuários do sistema.

## 📂 Estrutura

```
uploads/
├── clientes/          # Fotos de clientes (300x300px)
│   ├── .htaccess     # Proteção de segurança
│   └── .gitkeep      # Mantém pasta no Git
├── perfil/           # Fotos de perfil dos professores
└── README.md         # Este arquivo
```

## 🔒 Segurança

- Cada subdiretório tem seu próprio `.htaccess`
- Apenas imagens são permitidas (jpg, jpeg, png, gif, webp)
- Arquivos PHP são bloqueados
- Listagem de diretório desabilitada

## 📸 Fotos de Clientes

**Localização:** `/public/uploads/clientes/`
**Formato:** PNG (300x300px)
**Nomenclatura:** `cliente_[uniqid]_[timestamp].png`

### Exemplo:
```
cliente_6543210abc_1698765432.png
```

## 🎯 Uso no Código

### Salvar foto:
```php
$uploadDir = __DIR__ . '/uploads/clientes/';
$caminhoCompleto = $uploadDir . $nomeArquivo;
file_put_contents($caminhoCompleto, $data);

// Salvar no banco:
$fotoPath = '/public/uploads/clientes/' . $nomeArquivo;
```

### Exibir foto:
```html
<img src="<?= $cliente['foto'] ?>" alt="Foto do cliente">
<!-- Renderiza: /public/uploads/clientes/cliente_123.png -->
```

## 🗑️ Limpeza

Fotos antigas são automaticamente deletadas quando:
- Cliente troca de foto
- Cliente remove a foto
- Cliente é deletado (soft delete mantém foto)

---

**Autor:** Dante Testa (https://dantetesta.com.br)
**Data:** 01/11/2025
