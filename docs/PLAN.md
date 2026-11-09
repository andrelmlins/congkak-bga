# Plano e notas

Arquivo de trabalho interno. As regras ficam em [RULES.md](RULES.md), a arte em [ASSETS.md](ASSETS.md).

## Estado

- O jogo foi feito em julho de 2025 (15 commits, de 2025-07-01 a 2025-07-29), no scaffold **antigo** do BGA (`states.inc.php`, `Game.php` herdando de `\Table`, sem classes de estado). Os dois últimos commits citam números de bug (#177979, #175652).
- O manual de regras original se perdeu. Em **2026-11-09** o [RULES.md](RULES.md) foi reconstituído a partir do código e de duas fontes da internet. Ele ainda **não foi revisado pelo André**: a seção 12 lista o que precisa da confirmação dele.
- Em 2026-11-09 o André mandou o manual que ele próprio tinha montado ("Manual do Congkak, modo por rodadas", 3 páginas). **Não será guardado no repositório** (decisão dele): o `RULES.md` vai sendo atualizado e é ele a fonte. A comparação com o jogo está na seção 13 do RULES.md, e as diferenças A a I foram decididas no mesmo dia (J ficou para depois).
- Os documentos desta pasta (`RULES.md`, `PLAN.md`, `ASSETS.md`, e depois `MIGRATION.md`) e o `CLAUDE.md` foram criados no mesmo dia, para seguir o esquema dos jogos mais recentes (trucomineiro, trucopaulista, buraco).

## Testado no Studio

- **2026-11-09, pelo André:** depois da migração parcial (estados 1 e 99 fora, `setupNewGame` com `return 2;`, `playerScore`, HTML montado em `setup()`, `exception_on_warning` ligado, `Game.php` estendendo `Bga\GameFramework\Table`), **a partida iniciou e dá para jogar**. Ele não disse o que mais conferiu, então o resto da lista abaixo continua sem confirmação (placar no painel, fim de partida, animação da queima, abertura simultânea).

## Não testado

Nesta sessão **nada foi rodado**: não houve build, nem deploy, nem partida no Studio. O RULES.md vem só da leitura do código (`modules/php/Managers/`, `src/states/sowing.ts`) e das duas páginas da internet citadas nele. Pode haver caso que o código trata de um jeito que a leitura não pegou.

O PDF de regras da IIUM (`centre.iium.edu.my`, tabela de regras de torneio) não pôde ser lido, então não foi usado.

## Decisões

Decisões de **2026-11-09**, depois de comparar o manual do André com o jogo (RULES.md, seção 13). Nenhuma mudou o código:

- **Vale o jogo** nos pontos A (empate de rodada dá vitória aos dois), B (reabastecer até 7 por casa), C (sobras), E (a soma é "sementes na última rodada"), G (fim antecipado ao passar da metade das vitórias), H (captura com a oposta vazia) e I (ordem da mesa na abertura simultânea).
- **F:** sem desempate no resultado geral.
- **J:** variações opcionais do manual (reativar casas, banco de sementes, tempo, reinício completo, rodada extra) ficam para avaliar depois.
- **D (queima total):** manter como está (7 casas queimadas, ou 6 e a sétima com 1 semente, sem comparar o depósito). Opções descartadas no RULES.md, seção 13.
- **Sentido horário** confirmado pela pesquisa do André. **Casa queimada continua pulada** na semeadura (não confisca). **Ninguém joga de novo** na abertura: impossível na primeira (simulação dos 49 pares, nenhum caso), possível só nas rodadas simultâneas seguintes, onde o primeiro da mesa começa.
- **Quem começa a rodada seguinte:** manter como está por enquanto (quem tem menos sementes no total depois do reabastecimento, em empate o primeiro da mesa). Pode voltar a ser discutido.
- Nada mais em aberto na seção 12. A origem das imagens está registrada (geradas no ChatGPT, ASSETS.md).

- Sentido horário da semeadura: escolha deliberada do projeto, não erro (ver seção 12 do RULES.md). Uma variante anti-horária como opção de mesa já foi discutida, não implementada.

## O que falta para ficar como os jogos recentes

O plano em fases (com ordem, riscos e decisões pendentes) está em [MIGRATION.md](MIGRATION.md). A lista abaixo é o inventário que deu origem a ele.

Resultado de rodar o `review_checks.py` da skill `andre-bga` e de ler o projeto. O que está marcado com [x] foi feito; o resto não.

**Estrutura**

- [~] Scaffold novo: classes de estado (Fase 3) e arquivos `.jsonc` e `Managers/` (Fase 4) feitos em 2026-11-09, **não testados no Studio**. Falta o cliente (Fases 5 e 6), ver docs/MIGRATION.md.
- [x] Regras puras em `modules/php/Rules/` com testes em `tests/`: **feito em 2026-11-09** (Fase 1 de [MIGRATION.md](MIGRATION.md)), 10 arquivos de teste passando (ver a Fase 8 de MIGRATION.md). **Não confirmado no Studio**: falta jogar uma rodada (abertura, captura, reabastecimento) para ver que nada mudou.
- [x] Casas posicionadas à mão sobre os buracos da imagem (2026-11-09): o anel azul de seleção ficava fora do centro porque a grade tinha células de ~118 px e os buracos medem ~105 px, espaçados de forma irregular. **Não visto no Studio.**
- [x] Quadro de ajuda do jogador (2026-11-09, pedido de um jogador no BGA: "não entendo quando continua, quando ganho sementes"): `HelpManager` mostra os 4 finais de uma semeadura e como a rodada acaba. Fica num botão "?" no canto do tabuleiro que abre o quadro **por cima do tabuleiro**, sem ocupar espaço da página (o tabuleiro continua cabendo na primeira visualização). Abre sozinho na primeira vez e fecha pelo botão, por clique fora ou Esc. Textos novos em inglês com `_()`, a traduzir. **Não visto no Studio, sem commit ainda.** Falta, se quiser: dica no título da barra de status depois de cada final, e tooltip nas casas.
- [x] Build com pnpm e rollup, como os outros (Fase 5 da migração, 2026-11-09, `pnpm run build` roda sem erro, **jogo não aberto no Studio**).
- [x] `SeedingManager.php` (agora 234 linhas) e `HouseManager.php` (219) saíram do limite ao passar as regras para `Rules/`.
- [ ] `src/utils.ts` tem nome genérico: renomear por domínio.

**Cliente e layout**

- [x] `@font-face` do Google: **feito em 2026-11-09**. O símbolo de infinito (total de rodadas na Queima total) agora é o caractere `&infin;`, sem fonte nenhuma, e o `@font-face` e a classe `.material-symbols-outlined` saíram do SCSS. Não visto na tela.
- [ ] Layout com `@media` (740 px da janela): trocar por `@container` só faz sentido junto com baixar `game_interface_width.min` para 320 (item abaixo). Com o mínimo em 740 o BGA nunca deixa a caixa do jogo mais estreita que isso, e a consulta de container nunca dispararia. Precisa de teste visual no celular, por isso ficou.
- [ ] `game_interface_width.min` está em 740 em `gameinfos.jsonc`; os recentes usam 320 para funcionar no celular. Só mexer se o layout for refeito para isso.
- [ ] Sem chamada de `preloadImages` para as três imagens.

**Bugs vistos na leitura**

- [x] **Corrigido em 2026-11-09:** `HouseManager::isGameEnd()` tinha um `echo json_encode($item);` esquecido no ramo de 6 casas queimadas, que estraga a resposta do servidor. Removido.
- [x] **Corrigido em 2026-11-09:** `StepManager::stNewRound()` testava `$lockeds > 0` com um array (sempre verdadeiro). Agora `count($lockeds) > 0`. O resultado na prática é o mesmo, porque só entra no laço quando há casas para queimar.
- Texto do log "Players finished seeding in the same hole, one player will be randomly chosen" mas o código escolhe o jogador 1 da mesa (que é sorteado pelo BGA, mas não é um sorteio novo).
- O zombie agora escolhe uma casa permitida ao acaso (`bga_rand`), nível 1 do `Zombie Mode` (feito na Fase 3 da migração, 2026-11-09, não testado no Studio). O nível declarado nos metadados deve ser 1.

**Avisos das verificações do Studio (2026-11-09)**

Saída do "Project code checks" depois do deploy. Imagens, SQL e CSS passaram ("All good"), e recarregar informações do jogo, opções, preferências, estatísticas e traduções deu certo. O que o Studio apontou:

- [x] `extends \APP_GameClass` nos 5 serviços: removido (eles só usam `$this->game->...`, nunca as próprias funções de banco). Feito em 2026-11-09, **não testado no Studio**.
- [x] `require_once(.../table.game.php)`, `extends \Table` e `getGameName()` em `Game.php`: agora `use Bga\GameFramework\Table;`, `class Game extends Table`, sem o `require_once` e sem `getGameName`. Também troquei `static::DbQuery` por `$this->DbQuery` (o aviso do PHP 8.4). Feito em 2026-11-09, **não testado no Studio**.
- [x] Estados 1 e 99 saíram do `states.inc.php` e `setupNewGame` termina com `return NewRound::class;`. As transições para `gameEnd` (99) continuam, o framework fornece esse estado. Feito em 2026-11-09, **não testado no Studio**: é o que decide se a partida começa e termina, então é a primeira coisa a olhar.
- [x] `congkak_congkak.tpl` removido em 2026-11-09 e o HTML da mesa (caixa, rodada, tabuleiro e grade) agora é montado em `setup()` por `setupGameArea()` em `src/congkak.ts`, com `bga.gameArea.getElement()`. **Não testado no Studio.** Não existe `congkak.view.php` no repositório: se ele existir no servidor, apagar lá por SFTP.
- [x] `this.scoreCtrl` e `overall_player_board`: o código de pontuação do cliente (inscrição `score`, `scoreNotif`, `ScoreNotif`) foi apagado, e a animação das sementes que saem do jogo agora vai para `bga.playerPanels.getElement(playerId)`. Feito em 2026-11-09, **não testado no Studio**.
- [x] `PlayerManager::updateScore` agora usa `$this->bga->playerScore->set()`, que manda a notificação de pontuação sozinho (a notificação `score` própria deixou de existir). Feito em 2026-11-09, **não testado no Studio**. Conferir que o placar do painel muda ao fim de cada rodada, e que o fim da partida mostra as pontuações certas.
- [x] `is_beta` em `gameinfos.inc.php`: chave removida do arquivo em 2026-11-09 (o valor fica no Game Metadata Manager do Studio, que já tinha o `gameinfos.json` novo no servidor).
- [x] `exception_on_warning`: ligado em `gameinfos.inc.php` em 2026-11-09, para ver os avisos de PHP que hoje passam. **Não testado no Studio**: se algum aviso virar erro numa partida, o log do Studio mostra onde. Não há mais `static::` nem `self::` no PHP.

**Documentos**

- [x] Pontos da seção 12 do RULES.md confirmados com o André em 2026-11-09, e a origem das imagens registrada (ASSETS.md).
- [ ] Se o jogo for ao ar com o novo manual, gerar a versão para jogadores (o RULES.md atual é de trabalho, em português, e a ajuda do BGA é em inglês).
- [ ] `docs` e `CLAUDE.md` não estão na lista `ignore` do `.vscode/sftp.json` (só `.vscode`, `.git`, `src`, `node_modules` e poucos mais): acrescentar `docs`, `CLAUDE.md` e `tests` para não subirem ao Studio. O arquivo tem a senha do Studio, não foi aberto nem alterado nesta sessão.

## Estatísticas (para a ajuda e o log)

Sete por jogador, definidas em `stats.jsonc`: `seedsStorehouse`, `sowings`, `seedsMyHouse`, `sowingsOpponentHouse`, `seedsGameEnd`, `roundsWon` e `averageSeedsRound`.
