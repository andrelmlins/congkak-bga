# Assets

Três imagens em `img/` (o BGA pede PNG ou JPG, sem subpastas). Descritas em palavras porque sessões futuras muitas vezes não conseguem ver a arte.

**Origem e licença:** as três imagens (tabuleiro, sementes e fundo) foram **geradas no ChatGPT** (informação do André, 2026-11-09). Não há arquivo de licença no projeto além do `LICENCE_BGA` (a licença do código para a plataforma). Os prompts usados não foram guardados.

## Tabuleiro: `img/table.png`

- 1472 × 342 px, PNG com transparência, ~0,95 MB (aspecto 1472/342).
- Um tabuleiro de madeira marrom escura, comprido e de pontas arredondadas, visto de cima, com uma sombra suave por baixo e dois pezinhos pequenos na borda de baixo.
- Duas fileiras de **7 covas redondas** de mesmo tamanho (uma fileira em cima, uma embaixo), e em cada ponta uma cova **grande e oval**, o armazém (rumah). Na ponta de cada lado há um pino de madeira redondo que sai da borda.
- A madeira tem veios finos e as covas são fundas e escuras, com sombra interna.
- O CSS usa a imagem como fundo de `.congkak-table` (`background-size: 100%`) e posiciona em cima dela, em porcentagem, a grade das casas (`.congkak-grid`, 7 colunas) e os dois armazéns. Mexer nas proporções da imagem desalinha tudo.

## Sementes: `img/seeds.png`

- 800 × 200 px, PNG com transparência, ~150 KB: um sprite de **4 sementes** lado a lado, cada uma numa célula de 200 × 200 px.
- Cada semente é uma bolinha de vidro (como uma bola de gude olho de gato) com brilho branco no canto de cima à esquerda e um miolo em forma de folha ou chama:
  1. verde, com miolo verde escuro;
  2. azul esverdeado claro, com miolo turquesa e uma faixa âmbar;
  3. âmbar (laranja amarelado), com miolo marrom;
  4. vermelha, com miolo vermelho escuro.
- As cores são só enfeite. `src/ts/Managers/HouseManager.ts` (`fill`) dá a cada semente um atributo `position` de 1 a 4 sorteado, e o CSS escolhe a coluna do sprite por ele (`background-size: 400% 100%`).
- Dentro de uma casa, as sementes são colocadas em **anéis concêntricos** calculados no SCSS (`src/scss/Constants.scss`, usadas em `src/scss/Seeds.scss`, listas `$positions` com 50 posições e `$rumahpositions` com 98). Cada semente é o n-ésimo filho da casa e pega a n-ésima posição da lista.

## Fundo: `img/background.png`

- 1536 × 1024 px, PNG sem transparência, ~2,4 MB (o maior arquivo do `img/`).
- Papel amarelo claro (creme) com desenhos de contorno em um amarelo um pouco mais escuro, quase tom sobre tom: flores de 6 a 8 pétalas, estrelinhas e ramos de folhas, espalhados e em vários ângulos, com pontinhos soltos.
- Usado em `html` com `background-size: auto` (repete como ladrilho).

## Elementos de tela feitos em CSS

- **Casa selecionável:** um círculo azul translúcido com borda azul sobre a casa (`.selectable`). **Selecionada:** o mesmo círculo em vermelho (`.selected`).
- **Casa queimada:** um disco branco translúcido sobre a casa, e o contador fica cinza com letra branca (`.locked`).
- **Contador:** uma bolinha branca com sombra, com o número de sementes, sobre a borda de cima de cada casa e armazém (`ebg.counter`).
- **Nome dos jogadores:** uma caixa branca com sombra, na cor do jogador, em cima (adversário) e embaixo (você), com uma seta apontando o sentido: para a esquerda embaixo, para a direita em cima, formando o giro horário. A seta é um ícone Font Awesome.
- **Rodada:** a caixa "Round: atual / total". Quando não há limite de rodadas (Queima total), o total é o caractere de infinito (`&infin;`).

## Fontes

O jogo não usa fonte própria. O infinito é um caractere comum, e as setas dos nomes são ícones Font Awesome que o BGA já carrega. Até 2026-11-09 o infinito vinha de uma Material Symbols carregada do Google por `@font-face`, o que foi removido.
