# 🎮 Número Secreto — PHP Game

Jogo de adivinhação com estética de terminal retrô, feito em **PHP 8.2 puro**, sem banco de dados, pronto para deploy no **Azure App Services**.

## 🎯 Como jogar

- O sistema gera um número secreto entre **1 e 100**
- Você tem **7 tentativas** para adivinhar
- A cada chute, o sistema indica se o número é **maior ou menor**
- Um medidor de temperatura mostra se você está **🔥 Quente**, **〰 Morno** ou **❄ Frio**

---

## 🗂️ Estrutura do projeto

```
numero-secreto/
├── index.php      # Jogo completo (lógica + UI em arquivo único)
├── web.config     # Configuração para Azure App Services (IIS)
└── README.md      # Este arquivo
```

---

## ☁️ Deploy no Azure App Services

### Pré-requisitos

- Recurso **App Service** criado no Azure (Linux ou Windows)
- **PHP 8.2** selecionado como runtime
- **GitHub Actions** ou **Deployment Center** configurado

### Opção 1 — Deploy via GitHub Actions (recomendado)

1. Faça o push deste repositório para o GitHub
2. No portal Azure, vá em **App Service → Deployment Center**
3. Selecione **GitHub** como fonte
4. Autorize e selecione o repositório/branch
5. O Azure cria automaticamente o workflow `.github/workflows/` e realiza o deploy

### Opção 2 — Deploy via Azure CLI

```bash
az webapp up \
  --name <nome-do-seu-app> \
  --resource-group <seu-resource-group> \
  --runtime "PHP:8.2" \
  --sku F1
```

### Opção 3 — Deploy via ZIP

```bash
# Compactar os arquivos
zip -r game.zip index.php web.config

# Fazer upload via CLI
az webapp deployment source config-zip \
  --resource-group <rg> \
  --name <app-name> \
  --src game.zip
```

---

## ⚙️ Configurações recomendadas no Azure

| Configuração              | Valor recomendado |
|---------------------------|-------------------|
| PHP Version               | 8.2               |
| Always On                 | Ligado (evita cold start) |
| HTTPS Only                | Ligado            |
| Session storage           | Sistema de arquivos local (padrão) |

### Variáveis de ambiente (Application Settings)

Não são necessárias — o jogo usa apenas `$_SESSION` e não precisa de conexão externa.

---

## 🔒 Segurança

- Sem banco de dados → sem SQL injection
- Sem upload de arquivos
- Headers de segurança configurados no `web.config`
- Validação de input com `FILTER_VALIDATE_INT`
- `htmlspecialchars()` em todo output de usuário

---

## 🛠️ Requisitos técnicos

- PHP **8.2+**
- Extensões: nenhuma extra necessária (só `session`, padrão em todas as instalações)
- Servidor: IIS (Windows App Service) ou Apache/Nginx (Linux App Service)

---

## 📝 Licença

MIT — livre para usar, modificar e distribuir.
