$ErrorActionPreference = "Stop"
$output = Join-Path (Get-Location) "PAP-Cuida-Plus-atualizado-v3.pptx"
$appShot = Join-Path (Get-Location) "captura-aplicacao-inicio.png"
$pp = New-Object -ComObject PowerPoint.Application
$presentation = $pp.Presentations.Add()
$presentation.PageSetup.SlideSize = 15

$navy = 0x211B17
$blue = 0xE86E0D
$cyan = 0xD7B04B
$light = 0xF7F9FC
$dark = 0x263238
$muted = 0x687684
$white = 0xFFFFFF

function Add-Text($slide, $text, $left, $top, $width, $height, $size, $color, $bold=$false, $font="Aptos") {
    $box = $slide.Shapes.AddTextbox(1, $left, $top, $width, $height)
    $box.TextFrame.TextRange.Text = $text
    $box.TextFrame.TextRange.Font.Name = $font
    $box.TextFrame.TextRange.Font.Size = $size
    $box.TextFrame.TextRange.Font.Color.RGB = $color
    $box.TextFrame.TextRange.Font.Bold = $bold
    $box.TextFrame.WordWrap = -1
    $box.TextFrame.MarginLeft = 0
    $box.TextFrame.MarginRight = 0
    $box.TextFrame.MarginTop = 0
    $box.TextFrame.MarginBottom = 0
    return $box
}
function Add-Rect($slide, $left, $top, $width, $height, $fill, $radius=$false) {
    $type = if ($radius) { 5 } else { 1 }
    $shape = $slide.Shapes.AddShape($type, $left, $top, $width, $height)
    $shape.Fill.ForeColor.RGB = $fill
    $shape.Line.Visible = 0
    return $shape
}
function Add-Base($title, $section, $number) {
    $slide = $presentation.Slides.Add($presentation.Slides.Count + 1, 12)
    Add-Rect $slide 0 0 960 540 $light | Out-Null
    Add-Rect $slide 0 0 18 540 $blue | Out-Null
    Add-Text $slide $section.ToUpper() 55 34 260 18 10 $blue $true | Out-Null
    Add-Text $slide $title 55 57 800 45 27 $navy $true | Out-Null
    Add-Text $slide ("+Apoio  |  PAP  |  " + $number) 55 505 800 14 9 $muted $false | Out-Null
    return $slide
}
function Add-BulletList($slide, $items, $left, $top, $width, $size=20) {
    $y = $top
    foreach ($item in $items) {
        Add-Rect $slide $left ($y+5) 9 9 $blue $true | Out-Null
        Add-Text $slide $item ($left+24) $y $width 34 $size $dark $false | Out-Null
        $y += 52
    }
}
function Add-Card($slide, $title, $body, $left, $top, $width, $height, $accent=$blue) {
    Add-Rect $slide $left $top $width $height $white $true | Out-Null
    Add-Rect $slide $left $top 7 $height $accent | Out-Null
    Add-Text $slide $title ($left+22) ($top+18) ($width-40) 27 17 $navy $true | Out-Null
    Add-Text $slide $body ($left+22) ($top+55) ($width-40) ($height-68) 14 $muted $false | Out-Null
}
function Add-Picture($slide, $path, $left, $top, $width, $height) {
    if (Test-Path $path) {
        $picture = $slide.Shapes.AddPicture($path, 0, -1, $left, $top, $width, $height)
        $picture.Line.Visible = -1
        $picture.Line.ForeColor.RGB = $cyan
        $picture.Line.Weight = 2
    }
}

# 1 - Capa
$slide = $presentation.Slides.Add(1, 12)
Add-Rect $slide 0 0 960 540 $navy | Out-Null
Add-Rect $slide 0 0 960 16 $blue | Out-Null
Add-Rect $slide 660 0 300 540 $blue | Out-Null
Add-Rect $slide 705 105 165 165 $cyan $true | Out-Null
Add-Text $slide "+" 752 120 75 100 76 $navy $true "Aptos Display" | Out-Null
Add-Text $slide "+Apoio" 70 94 520 70 45 $white $true "Aptos Display" | Out-Null
Add-Text $slide "Plataforma de apoio domiciliário" 74 174 540 40 25 $cyan $true | Out-Null
Add-Text $slide "Uma solução digital para ligar beneficiários, médicos voluntários e coordenação." 74 245 500 58 19 $white $false | Out-Null
Add-Rect $slide 74 350 430 2 0x6A8EAE | Out-Null
Add-Text $slide "Prova de Aptidão Profissional" 74 382 300 24 14 $white $true | Out-Null
Add-Text $slide "Nome: ____________________    Curso: ____________________" 74 419 500 22 12 $cyan $false | Out-Null
Add-Text $slide "Escola: ____________________  |  2026" 74 450 500 22 12 $cyan $false | Out-Null

# 2 - Contexto
$slide = Add-Base "Por que é necessária?" "1. Problema" "02"
Add-Text $slide "No apoio domiciliário, a informação precisa de estar disponível no momento certo." 55 125 810 48 24 $navy $true | Out-Null
Add-Card $slide "Informação dispersa" "Registos em papel ou ficheiros separados dificultam a consulta e aumentam o risco de falhas." 55 220 250 160 $blue
Add-Card $slide "Coordenação difícil" "É necessário relacionar beneficiários, médicos, horários, necessidades e estado das visitas." 335 220 250 160 $cyan
Add-Card $slide "Deslocações" "O profissional precisa de chegar rapidamente à morada correta do beneficiário." 615 220 250 160 0x6A8EAE
Add-Text $slide "A proposta é centralizar a gestão num painel simples, visual e preparado para evoluir." 55 430 810 30 18 $muted $false | Out-Null

# 3 - Objetivos
$slide = Add-Base "A resposta: +Apoio" "2. Solução" "03"
Add-Text $slide "Uma plataforma única para organizar, acompanhar e cuidar." 55 125 810 40 24 $navy $true | Out-Null
Add-BulletList $slide @("Centralizar os dados dos beneficiários, médicos e apoios.","Permitir criar, editar, pesquisar e eliminar registos.","Acompanhar a agenda e o estado de cada apoio.","Facilitar a deslocação até ao beneficiário através de GPS.","Criar uma base sólida para uma aplicação real com autenticação e permissões.") 75 185 790 21
Add-Rect $slide 690 120 150 285 $blue $true | Out-Null
Add-Text $slide "FOCO" 722 158 90 24 13 $white $true | Out-Null
Add-Text $slide "Organizar -  - Acompanhar -  - Cuidar" 718 205 105 160 23 $white $true "Aptos Display" | Out-Null

# 4 - Funcionalidades
$slide = Add-Base "Funcionalidades principais" "Solução" "04"
Add-Card $slide "Dashboard" "Indicadores de beneficiários, médicos, apoios agendados e casos prioritários." 55 125 260 135 $blue
Add-Card $slide "Beneficiários" "Dados pessoais, morada, necessidade, prioridade, contacto e observações." 350 125 260 135 $cyan
Add-Card $slide "Médicos voluntários" "Especialidade, disponibilidade, contacto, localidade e áreas de apoio." 645 125 260 135 0x6A8EAE
Add-Card $slide "Agenda de apoios" "Agendamento, tipo de visita, médico responsável, beneficiário e estado." 55 300 260 135 0x6A8EAE
Add-Card $slide "Pesquisa e relatórios" "Filtros, resumo operacional, exportação e cópia de segurança dos dados." 350 300 260 135 $blue
Add-Card $slide "Navegação GPS" "Ação de rota que usa a localização do dispositivo e abre o Google Maps." 645 300 260 135 $cyan

# 5 - Dados
$slide = Add-Base "Gestão de dados" "Implementação" "05"
Add-Text $slide "A aplicação evoluiu de uma demonstração local para uma arquitetura com servidor." 55 120 820 32 21 $navy $true | Out-Null
Add-Rect $slide 80 220 220 85 $blue $true | Out-Null
Add-Text $slide "Interface - HTML + CSS + JavaScript" 105 240 170 48 18 $white $true | Out-Null
Add-Text $slide "->" 345 238 45 42 32 $blue $true | Out-Null
Add-Rect $slide 405 220 220 85 $cyan $true | Out-Null
Add-Text $slide "Servidor - PHP + PDO" 470 240 120 48 18 $navy $true | Out-Null
Add-Text $slide "->" 670 238 45 42 32 $blue $true | Out-Null
Add-Rect $slide 730 220 150 85 0x6A8EAE $true | Out-Null
Add-Text $slide "Base de dados - SQLite" 752 240 110 48 17 $white $true | Out-Null
Add-BulletList $slide @("A base é criada automaticamente na primeira execução.","Os dados ficam disponíveis para a aplicação através da API PHP.","O SQLite é gratuito, leve e adequado para o protótipo da PAP.") 95 365 760 17

# 6 - GPS
$slide = Add-Base "Sistema de navegação GPS" "Funcionalidade" "06"
Add-Rect $slide 65 125 330 315 0xE8F1F8 $true | Out-Null
Add-Text $slide "Rota para o beneficiário" 95 160 270 30 21 $navy $true | Out-Null
Add-Text $slide "1" 105 225 40 40 24 $white $true | Out-Null
Add-Rect $slide 105 225 40 40 $blue $true | Out-Null
Add-Text $slide "O médico seleciona o beneficiário." 165 230 190 34 16 $dark $false | Out-Null
Add-Rect $slide 105 295 40 40 $cyan $true | Out-Null
Add-Text $slide "2" 117 301 20 28 18 $navy $true | Out-Null
Add-Text $slide "A aplicação pede a localização atual." 165 300 190 45 16 $dark $false | Out-Null
Add-Rect $slide 105 365 40 40 0x6A8EAE $true | Out-Null
Add-Text $slide "3" 117 371 20 28 18 $white $true | Out-Null
Add-Text $slide "A rota abre no Google Maps." 165 370 190 40 16 $dark $false | Out-Null
Add-Text $slide "GPS" 520 185 190 90 62 $blue $true "Aptos Display" | Out-Null
Add-Text $slide "Se a localização for recusada, a rota continua a funcionar usando a morada do beneficiário." 520 300 300 85 21 $navy $true | Out-Null

# 7 - Tecnologias
$slide = Add-Base "Tecnologias utilizadas" "Implementação" "07"
Add-Card $slide "HTML" "Estrutura das páginas e formulários da aplicação." 70 140 245 150 $blue
Add-Card $slide "CSS + Bootstrap" "Layout responsivo, cartões, tabelas, modais e componentes visuais." 355 140 245 150 $cyan
Add-Card $slide "JavaScript" "Navegação, validação, filtros, renderização e integração GPS." 640 140 245 150 0x6A8EAE
Add-Card $slide "PHP" "API no servidor para receber, validar e guardar os dados." 215 335 245 150 0x6A8EAE
Add-Card $slide "SQLite + PDO" "Base de dados relacional local, sem custos de instalação de servidor de base de dados." 500 335 245 150 $blue

# 8 - Arquitetura
$slide = Add-Base "Como funciona" "Arquitetura" "08"
Add-Text $slide "Fluxo de uma operação na aplicação" 55 125 600 30 22 $navy $true | Out-Null
Add-Rect $slide 70 210 170 90 $blue $true | Out-Null
Add-Text $slide "Utilizador - Médico / coordenação" 92 236 125 45 17 $white $true | Out-Null
Add-Text $slide "->" 270 230 45 42 34 $blue $true | Out-Null
Add-Rect $slide 340 210 170 90 $cyan $true | Out-Null
Add-Text $slide "Interface - Cuida+" 385 236 90 45 17 $navy $true | Out-Null
Add-Text $slide "->" 540 230 45 42 34 $blue $true | Out-Null
Add-Rect $slide 610 210 170 90 0x6A8EAE $true | Out-Null
Add-Text $slide "API PHP - api.php" 655 236 90 45 17 $white $true | Out-Null
Add-Text $slide "->" 810 230 45 42 34 $blue $true | Out-Null
Add-Rect $slide 850 210 75 90 $navy $true | Out-Null
Add-Text $slide "SQLite" 861 245 54 25 13 $white $true | Out-Null
Add-Text $slide "A interface mantém uma cópia local de segurança, enquanto o servidor centraliza os dados quando o PHP está disponível." 90 375 760 55 21 $muted $false | Out-Null

# 9 - Demonstração
$slide = Add-Base "Demonstração da aplicação" "Apresentação" "09"
Add-Rect $slide 55 118 500 300 $white $true | Out-Null
Add-Picture $slide $appShot 65 128 480 270
Add-Text $slide "Interface real da aplicação" 65 407 480 22 13 $muted $false | Out-Null
Add-Text $slide "Um fluxo simples para cada visita" 610 128 280 32 21 $navy $true | Out-Null
Add-BulletList $slide @("Consultar o dashboard e os indicadores.","Pesquisar beneficiários e necessidades.","Agendar apoios e atualizar estados.","Abrir a rota GPS para a visita.") 615 190 275 20
Add-Rect $slide 615 405 240 42 $blue $true | Out-Null
Add-Text $slide "Do pedido ao acompanhamento" 637 417 200 20 13 $white $true | Out-Null

# 10 - Segurança e futuro
# 10 - Para que serve
$slide = Add-Base "Para que serve o +Apoio?" "Como utilizar" "10"
Add-Picture $slide (Join-Path (Get-Location) "captura-aplicacao-entrada.png") 55 120 270 152
Add-Picture $slide (Join-Path (Get-Location) "captura-aplicacao-login.png") 345 120 270 152
Add-Picture $slide (Join-Path (Get-Location) "captura-aplicacao-registo.png") 635 120 270 152
Add-Text $slide "1. Entrar" 55 280 270 22 14 $blue $true | Out-Null
Add-Text $slide "2. Autenticar" 345 280 270 22 14 $blue $true | Out-Null
Add-Text $slide "3. Criar conta" 635 280 270 22 14 $blue $true | Out-Null
Add-Card $slide "Para que serve?" "Centraliza pedidos, beneficiários, médicos voluntários e acompanhamento das visitas num só espaço." 55 330 260 125 $blue
Add-Card $slide "Como utilizar?" "Entrar, escolher o perfil, consultar a informação necessária e registar cada apoio realizado." 350 330 260 125 $cyan
Add-Card $slide "Quem pode usar?" "Beneficiários, médicos voluntários e coordenação, cada um com as opções adequadas ao seu perfil." 645 330 260 125 0x6A8EAE

# 11 - Segurança e futuro
$slide = Add-Base "Conclusão e evolução" "Encerramento" "11"
Add-Text $slide "O que já foi alcançado" 65 125 350 30 22 $navy $true | Out-Null
Add-BulletList $slide @("Protótipo funcional de apoio domiciliário.","Gestão integrada de informação operacional.","Base SQLite ligada através de PHP.","Navegação GPS para apoiar as visitas.") 75 180 390 18
Add-Text $slide "Próximos passos" 540 125 300 30 22 $navy $true | Out-Null
Add-BulletList $slide @("Autenticação e perfis de utilizador.","Permissões e auditoria de acessos.","Cópias de segurança automáticas.","Cumprimento das regras de proteção de dados.") 550 180 340 18
Add-Rect $slide 65 430 825 42 $navy $true | Out-Null
Add-Text $slide "+Apoio : tecnologia para apoiar quem cuida." 250 442 460 22 17 $white $true | Out-Null

$presentation.SaveAs($output, 24)
$presentation.Close()
$pp.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($presentation) | Out-Null
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($pp) | Out-Null
Write-Output "Criada: $output"




