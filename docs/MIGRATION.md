# Plano de migração para a nova forma de programar do BGA

Criado em 2026-11-09. Complementa o [PLAN.md](PLAN.md) (estado e pendências) e segue o roteiro de migração do BGA (`BGA_Studio_Migration_Guide`) e as convenções dos jogos recentes (trucomineiro, trucopaulista, buraco). As decisões do André de 2026-11-09 estão na seção "Decisões". A Fase 0 está feita, e a Fase 1 foi a primeira a começar.

## Regras do processo

- **Uma fase por vez, um commit por fase** (Conventional Commits, só quando o André pedir).
- **Entre fases:** deploy, "Reload" no Studio quando mexer em configuração, **encerrar todas as mesas e abrir uma nova** (mesas em andamento guardam o estado antigo), jogar uma rodada inteira, olhar o log do Studio (`exception_on_warning` está ligado: qualquer aviso de PHP vira erro).
- **O jogo tem que continuar jogável ao fim de cada fase.** Se uma fase quebrar, voltar o commit e dividir a fase.
- Comportamento de regra **não muda** na migração. Qualquer mudança de regra é decisão à parte, com data no RULES.md.
- Antes de cada fase, rodar `wc -l` nos arquivos que ela toca (regra dos 250 e 300 linhas).

## Onde estamos

**Já no estilo novo (Fase 0, feita em 2026-11-09, jogo inicia e joga no Studio):**

- `Game` estende `Bga\GameFramework\Table`, sem `require_once`, sem `getGameName`.
- Sem os estados 1 e 99 no `states.inc.php`; `setupNewGame` devolve o primeiro estado (2).
- Serviços sem `APP_GameClass`.
- Pontuação por `$this->bga->playerScore->set()`.
- HTML da mesa montado em `setup()` (sem `.tpl`), painel de jogador por `bga.playerPanels`.
- `is_beta` fora do arquivo, `exception_on_warning` ligado.

**Ainda no estilo antigo:**

| Área | Hoje | Tamanho do trabalho |
|---|---|---|
| Estados | `states.inc.php` com 5 estados (`newRound` 2, `playersSeeding` 10, `playerSeeding` 11, `nextMultiplayers` 20, `nextPlayer` 21), e `st*`/`act*`/`arg*` espalhados em `Game.php` e nos serviços | grande |
| Chamadas de servidor | 17 `notifyAllPlayers`, 7 `getGameStateValue` (5 opções via `initGameStateLabels`), 27 `globals->`, 14 chamadas de estatística, 2 `BgaUserException`, `zombieTurn` | médio, mecânico |
| Regras | misturadas com banco e notificação em `SeedingManager`, `StepManager`, `HouseManager` | médio |
| Arquivos de configuração | `gameinfos.inc.php`, `gameoptions.json`, `gamepreferences.json`, `stats.json` | pequeno |
| Cliente | classe Dojo (`declare`, `define.ts`), `dojo.subscribe`, `simplePause` no servidor, `tsc` para um único `congkak.js` em ES5 que embute `bga-animations` e `bga-zoom` de `node_modules` | grande |
| Build | `yarn` + `tsc` + `sass` | pequeno |
| Layout | `@media` por janela, `game_interface_width.min` 740 | médio, precisa de olho |
| Arquivos grandes | `SeedingManager` 293 linhas, `HouseManager` 250 | pequeno |

## Fases

### Fase 1: regras puras e testes (rede de segurança, sem mudar comportamento)

**Feita em 2026-11-09 (código e testes); falta jogar uma rodada no Studio para confirmar que o comportamento não mudou.** O que ficou:

- `modules/php/Rules/`: `Board`, `SowingPath`, `Sowing`, `SowingEnding`, `SowingTurn` (uma semeadura, ou as duas da abertura), `Choices` (o que cada um pode escolher), `Opening` (quem segue depois da abertura), `Reseed`, `RoundResult`, `GameEnd`, `Scoring`.
- `tests/`: `SowingPathTest`, `SowingEndingTest`, `OpeningTest` (inclui os 49 pares da primeira abertura: 37 com os dois jogando, 12 com um, nenhum com ninguém, e as sementes sempre somam 98), `ReseedTest`, `RoundResultTest`. 145 verificações, todas passando. Rodar: `for t in tests/*Test.php; do php "$t" || exit 1; done`.
- `HouseManager`, `SeedingManager`, `StepManager` e `PlayerManager` agora só leem o banco, gravam (`HouseManager::save` grava só as casas que mudaram), mandam as notificações e respondem aos estados. A ordem das notificações e das gravações foi mantida.
- `SeedingManager` caiu de 293 para 234 linhas e `HouseManager` ficou com 219.

O plano original da fase continua abaixo, como referência.

Fazer **primeiro**, porque os testes seguram as fases seguintes.

1. Criar `modules/php/Rules/`, sem framework e sem banco, cada uma com um teste de PHP puro em `tests/` (`for t in tests/*Test.php; do php "$t"; done`):
   - `SowingPath`: o caminho da semeadura de um jogador (as suas 7 casas, o seu armazém, as 7 do adversário, pulando o armazém do adversário e as casas queimadas) e a próxima casa.
   - `SowingEnding`: dados a contagem da última casa e o lado, devolve um dos quatro finais (armazém, casa com sementes, captura, casa vazia do adversário).
   - `Opening`: a semeadura simultânea de dois jogadores, passo a passo, e quem pode jogar de novo. O teste inclui a invariante já simulada em 2026-11-09: **nos 49 pares de casas da primeira abertura, ninguém fica sem jogar** (37 pares com os dois, 12 com um).
   - `Reseed`: a partir do armazém e das casas já queimadas, quantas sementes vão a cada casa, quais queimam e quantas saem do jogo.
   - `RoundResult` (vencedor, empate dá vitória aos dois) e `EndOfGame` (7 queimadas, ou 6 e a sétima com 1 semente), mais a pontuação de cada modo de vitória.
2. `SeedingManager`, `StepManager`, `HouseManager` e `PlayerManager` passam a **chamar** essas regras e ficam só com banco, notificação e transição.
3. Pronto quando: todos os testes passam e uma rodada no Studio se comporta como antes.

Divide de quebra os dois arquivos grandes. Cada caso de teste ancora uma frase do [RULES.md](RULES.md).

### Fase 2: chamadas de servidor para a API nova (estados ainda legados)

**Feita em 2026-11-09, não testada no Studio.** O que mudou: `notifyAllPlayers` para `$this->bga->notify->all`, `globals->` para `$this->bga->globals->`, as 5 opções para `$this->bga->tableOptions->get($id)` (com o padrão de cada opção do `gameoptions.json`, em `OptionsManager`) e a preferência para `$this->bga->userPreferences`, estatísticas para `$this->bga->playerStats` (`init` de todas de uma vez, e `averageSeedsRound`, que nunca era inicializada, agora também é), `BgaUserException` para `\Bga\GameFramework\UserException` (sem o código 9000), e o `initGameStateLabels` saiu do `Game.php`. Sobrou de propósito: `giveExtraTime`, `getPlayerNameById`, `activeNextPlayer` e `gamestate->` (não estão obsoletos ou ficam para a Fase 3).

O plano original da fase continua abaixo.

Mecânico, em um commit:

- `initGameStateLabels` e `getGameStateValue` das 5 opções para `$this->bga->tableOptions->get($id)`, e a preferência para `$this->bga->userPreferences->get($playerId, $id)`.
- `$this->game->globals->...` para `$this->bga->globals->...`.
- `notifyAllPlayers` para `$this->bga->notify->all(...)`.
- Estatísticas para `$this->bga->tableStats` e `$this->bga->playerStats` (atenção: `playerStats->init` não recebe o id do jogador).
- `BgaUserException` para `\Bga\GameFramework\UserException`.
- `getPlayerNameById` e afins ficam; trocar `getActivePlayerName` e parecidos se aparecerem.

Pronto quando: nada de chamada antiga sobra por `grep`, e o jogo joga igual.

### Fase 3: estados como classes (`modules/php/States/`)

Um estado por vez, jogando uma rodada depois de cada um. Ordem sugerida do mais simples ao mais delicado:

1. `NextPlayer` (21, GAME): `giveExtraTime`, confere fim de rodada, `activeNextPlayer`.
2. `NewRound` (2, GAME): hoje é o maior `st*` (reabastecimento, queima, fim de partida).
3. `PlayerSeeding` (11, ACTIVE_PLAYER): `actPlayerSeeding(int $house, ..., int $activePlayerId)`, `getArgs`, `zombie`.
4. `PlayersSeeding` (10, MULTIPLE_ACTIVE_PLAYER) e `NextMultiplayers` (20, GAME): a abertura simultânea e o desempate quando ninguém joga de novo.

Pontos de atenção:

- O construtor do estado leva **só** `id` e `type`; as transições viram o `return NextState::class` das ações.
- **Em estado multiativo a ação NÃO devolve a classe do próximo estado**: o framework pula para ele logo depois do primeiro jogador (erro visto no Studio em 2026-11-09: `NextMultiplayers` entrou com só uma escolha gravada). Usar `setPlayerNonMultiactive($id, Proximo::class)` e devolver `void`. Só estados de jogador ativo único devolvem a classe.
- Em estado multiativo **não** existe `$activePlayerId`: usar `getCurrentPlayerId()` na ação, e fazer o `zombie` chamar uma função auxiliar que recebe o jogador (o `ZombieService` de hoje já passa o jogador).
- O zombie hoje escolhe sempre a primeira casa. Decidido: **nível 1** (uma casa legal ao acaso com `bga_rand`), declarado no Game Metadata Manager e igual em todos os estados.
- O que o próximo estado lê em `getArgs()` é gravado na ação, antes do `return`, não no `onEnteringState` (erro clássico "errado no primeiro desenho, certo depois do F5").
- Mudar o jogador ativo só em estado GAME, seguido de transição.
- Ao fim: apagar `states.inc.php`, `stNewRound`, `stNextPlayer`, `actPlayerSeeding` e afins de `Game.php`.

Pronto quando: `states.inc.php` não existe mais e a partida roda de ponta a ponta (rodada única, rodadas fixas, queima total).

**Feita em 2026-11-09, não testada no Studio** (só `php -l` e os testes de `tests/`). O que mudou: os 5 estados viraram classes em `modules/php/States/` (`NewRound` 2, `PlayersSeeding` 10, `PlayerSeeding` 11, `NextMultiplayers` 20, `NextPlayer` 21), com os nomes antigos passados em `name:` para o cliente não mudar. As classes só delegam: `StepManager::newRound/nextMultiplayers/nextPlayer` e `SeedingManager::actPlayerSeeding/recordPick/recordAutomaticPicks/automaticPick` devolvem a classe do próximo estado (ou `Game::ST_END_GAME`). O `states.inc.php`, o `ZombieService` e os wrappers do `Game.php` foram apagados. O zombie agora é nível 1 (casa permitida ao acaso com `bga_rand`) nos dois estados de escolha. A semeadura automática no estado multiativo grava a escolha sem `setPlayerNonMultiactive` e chama `setPlayersMultiactive($manuais, NextMultiplayers::class, true)`.

O que olhar no Studio: se a partida começa (`setupNewGame` devolve `NewRound::class`), se a abertura simultânea fecha sozinha quando os dois escolhem, se a semeadura automática funciona nas duas telas (inclusive quando os dois jogadores têm casa forçada e `$manuais` fica vazio), se o fim de partida chega ao estado 99 e se `globals->get` de uma chave inexistente devolve nulo (o código antigo dependia disso).

### Fase 4: arquivos de configuração e nomes

- `gameinfos.inc.php` para `gameinfos.jsonc`, `gameoptions.json` e `gamepreferences.json` para `.jsonc`, `stats.json` para `stats.jsonc`, copiando o formato do buraco. `designer`, `artist`, `year`, `tags`, `complexity` e afins vão para o Game Metadata Manager do Studio, não para o arquivo.
- Recarregar informações, opções, preferências e estatísticas no Studio.
- Renomear `modules/php/services/` para `Managers/` (`SeedingService` para `SeedingManager` e assim por diante), como nos outros jogos. É só nome, deixar por último.
- Apagar `Constants.php` se sobrar só o que o framework já dá (`getLocalCurrentPlayerId`, `localActiveNextPlayer`).

**Feita em 2026-11-09, não testada no Studio.** `gameinfos.inc.php`, `gameoptions.json`, `gamepreferences.json` e `stats.json` viraram `.jsonc` (mesmo conteúdo, com cabeçalho de comentário; `game_interface_width.min` continua 740 por decisão do André). `modules/php/services/` virou `modules/php/Managers/` (`HouseManager`, `PlayerManager`, `StepManager`, `StatsManager`, `OptionsManager`, `SeedingManager`, e as propriedades de `Game` com o mesmo nome) e `Constants.php` foi apagado (nada o usava). No Studio é preciso recarregar informações, opções, preferências e estatísticas, e conferir se o `gameinfos.jsonc` não conflita com o que está no Game Metadata Manager.

### Fase 5: build do cliente (sem mudar o código do jogo)

- `pnpm` + `rollup` + `typescript` como no trucomineiro: `package.json`, `rollup.config.mjs`, `tsconfig.json`, `pnpm-workspace.yaml`, saída em `modules/js/Game.js` e `congkak.css` (`sass` com `src/scss/Game.scss`).
- Trocar a pasta `src/sass` por `src/scss` com um parcial por família (`Board.scss`, `Seeds.scss`, `Houses.scss`, `Table.scss`, `Constants.scss`).
- Pronto quando: `pnpm run build` gera os dois arquivos e o jogo abre.

Esta fase só faz sentido **junto** com a seguinte (o `Game.js` novo é uma classe ES6, não uma classe Dojo).

### Fase 6: cliente na classe `Game` (ES6) com notificações por promessa

Hoje o cliente é `declare('bgagame.congkak', ebg.core.gamegui, new Congkak())`, com `dojo.subscribe` e pausas do servidor (`simplePause`). **Não misturar os dois estilos**, então a migração do cliente é inteira:

1. `src/ts/Game.ts` com `export class Game`, `setup(gamedatas)`, `onEnteringState`, `onLeavingState`, `onUpdateActionButtons`, só ligando as peças.
2. Bibliotecas por `globalThis.importEsmLib('bga-animations', '1.x')` em `libs.ts`, em vez de compilar `bga-animations` e `bga-zoom` de `node_modules`. O `BgaLocalAnimation` (o embrulho de `animation.ts`) é reescrito em cima do `Manager` da biblioteca. `bga-zoom` está no build mas não é usado: conferir e tirar.
3. Notificações viram `async notif_xxx(args)`, cada uma **aguardando** as animações que dispara (as sementes saindo de uma casa para outra, a captura, o reabastecimento). O contador muda **depois** do `await` do que ele conta.
4. **Sai o `simplePause` do servidor** (5 pontos hoje), porque com promessas ele soma ao tempo que o cliente já espera. A exceção legítima é uma pausa sem animação própria (fim da rodada, para ler o placar).
5. Troca de chamadas: `dojo.connect` por `addEventListener`, `dojo.destroy` por `remove()`, `addActionButton` por `this.bga.statusBar.addActionButton`, `bgaPerformAction` por `this.bga.actions.performAction`, `isCurrentPlayerActive` e afins por `this.bga.*`.
6. Um gerenciador por área da tela em `src/ts/Managers/` (casas, sementes, armazéns, caixa de rodada), um arquivo por estado em `src/ts/States/` e `Constants.ts` espelhando `Constants.scss`.
7. `bgaFormatText` em `FormatStrings.ts` (hoje vazio).
8. Pré-carregar as três imagens (`bga.images.preloadImages`).

Pronto quando: abertura simultânea, captura, fim de rodada, reabastecimento com queima e fim de partida funcionam e animam, sem erro no console.

**Fases 5 e 6 feitas juntas em 2026-11-09. `pnpm run build` roda sem erro e `tsc --noEmit` passa, mas o cliente novo NÃO foi aberto no Studio** (nem uma partida). O que mudou:

- Build: `package.json`, `rollup.config.mjs`, `tsconfig.json`, `pnpm-workspace.yaml` e `pnpm-lock.yaml` copiados do buraco (mesmas versões), `bga-framework.d.ts` na raiz, saída em `modules/js/Game.js` e `congkak.css`. `yarn.lock`, `congkak.js`, `src/framework.d.ts`, `define.ts` e o resto do cliente antigo saíram. O CSS gerado tem as mesmas 176 regras do antigo (conferido por comparação, só a ordem dos arquivos mudou).
- `src/scss/`: `Game.scss` (entrada, fundo da página), `Table.scss`, `Houses.scss`, `Seeds.scss`, `Constants.scss` (as posições das sementes).
- `src/ts/`: `Game.ts` (classe `Game`, `notif_*` assíncronos), `libs.ts` (`bga-animations` por `importEsmLib`), `Constants.ts` (tempos e nomes), `types.d.ts`, `Managers/` (`TableManager`, `HouseManager`, `SeedManager`, `SelectionManager`), `States/` (`PlayersSeeding`, `PlayerSeeding`). `BgaLocalAnimation` e o `bga-zoom` (que não era usado) saíram: as sementes andam por `animationManager.slideAndAttach`.
- As classes de estado do PHP perderam o `name:` (o cliente registra pelo nome da classe, como no buraco) e o servidor não manda mais `simplePause` (5 pontos): cada `notif_*` espera suas sementes pousarem, os contadores mudam depois, e `PAUSE` (500 ms, `Constants.ts`) dá um respiro entre uma jogada e outra.
- `FormatStrings.ts` e `bgaFormatText` não foram criados: o formatador antigo era vazio. A pré-carga de imagens também não: o BGA já carrega o que está em `img/`.

**Correção depois do primeiro teste no Studio (2026-11-09):** as sementes tremiam ao se mover. Duas causas prováveis, ambas tratadas e **ainda não vistas no Studio**: (1) o CSS posicionava a semente com `transform: translate(-50%, -50%)`, e o deslize da lib põe o seu próprio `transform` por cima, então a semente pulava meio tamanho no início e no fim; agora é `margin: -10% 0 0 -10%` (metade da semente, que mede 20% da casa). (2) A posição de uma semente na casa vem da ordem entre irmãs (`:nth-child`), então tirar uma semente do meio fazia as seguintes pularem para o lugar vazio; agora `HouseManager::seeds()` devolve as sementes da última para a primeira, e quem sai é sempre a última.

O que olhar no Studio (nada disso foi visto): se o jogo abre e as casas aparecem; se os estados `PlayersSeeding` e `PlayerSeeding` são achados pelo nome da classe; se `onPlayerActivationChange` roda ao entrar no estado multiativo (é de onde sai a seleção e o botão "Sow"); se as sementes que saem do jogo (deslizam para o painel do jogador e são removidas) aparecem durante o deslize, já que perdem o estilo da mesa ao serem coladas no painel (o código antigo fazia igual, mas com outra animação); se os contadores batem com as sementes depois de cada notificação; e se a pausa de 500 ms parece boa.

### Fase 7: layout para celular (cancelada)

**Decisão do André em 2026-11-09: a responsividade está boa, não mexer.** `game_interface_width.min` continua em 740 e o `@media` por janela continua. O `@container` fica como diferença conhecida em relação aos jogos recentes (o `review_checks.py` vai continuar apontando um `[GAP]`).

### Fase 8: fechamento

- Rodar `scripts/review_checks.py` da skill `andre-bga` e o "Project code checks" do Studio: sem `[GAP]` nem aviso.
- Atualizar o CLAUDE.md (estrutura nova, comando de build, testes), o PLAN.md e o ASSETS.md.
- Zombie e metadados conferidos no Game Metadata Manager.
- Variações do manual do André (J no RULES.md) só depois de tudo estável.

**Fase 8 feita em parte em 2026-11-09.** O `review_checks.py` roda sem aviso, com um só `[GAP]`: a falta de `@container`, que é a diferença conhecida da Fase 7 cancelada. Para fechar aquele aviso, as regras que só eram cobertas por outro teste ganharam o seu: `BoardTest`, `ChoicesTest`, `GameEndTest`, `ScoringTest`, `SowingTest` e `SowingTurnTest` (agora são 10 arquivos em `tests/`, todos passando; `RoundResultTest` perdeu o que foi para `GameEndTest` e `ScoringTest`). **Falta, e só o André pode fazer:** abrir uma partida no Studio (tudo das Fases 2 a 6 foi só compilado e testado em PHP puro), rodar o "Project code checks", declarar o zombie nível 1 no Game Metadata Manager e acrescentar `docs`, `CLAUDE.md`, `tests`, `bga-framework.d.ts`, `pnpm-lock.yaml`, `rollup.config.mjs` e afins à lista `ignore` do `.vscode/sftp.json` (não mexi nele, tem a senha), conferindo que `modules/js` **não** está ignorado.

## Decisões

Respondidas pelo André em 2026-11-09:

1. **Ordem:** a recomendada. Fase 1 (testes), depois 2 e 3 (servidor inteiro), e só então o cliente.
2. **Cliente:** migrar inteiro para promessas (Fases 5 e 6, sai o `simplePause`).
3. **Zombie:** nível 1 (jogada legal aleatória, com `bga_rand`). Declarar "1" no Game Metadata Manager e fazer todos os estados cumprirem.
4. **Celular:** não mexer (Fase 7 cancelada).
5. **Nomes:** renomear `services` para `Managers` (Fase 4).

## Risco e o que se perde se parar no meio

- Parar depois da **Fase 3**: servidor no estilo novo, cliente no antigo. Funciona, e o resultado já passa nas verificações de PHP do Studio. É um bom ponto de parada.
- Parar depois da **Fase 4**: igual, com configuração nova.
- As Fases 5 e 6 são **uma só** na prática, e a Fase 7 depende delas.
