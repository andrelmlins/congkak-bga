# Congkak: regras

Este manual foi **reconstituído em 2026-11-09**, porque o original se perdeu. Ele junta duas coisas: o que o jogo faz hoje (lido no código, em `modules/php/`) e os conceitos do congkak que aparecem na internet. Onde o código decide algo que a internet não cobre, ou decide diferente, o ponto está marcado como **[Decisão]** e repetido na seção 12, para o André confirmar ou mudar.

Depois de escrito, o André mandou o **manual que ele mesmo tinha montado** ("Manual do Congkak, modo por rodadas", 3 páginas, regras sugeridas e variações). Ele se declara uma proposta caseira, não um regulamento oficial. As diferenças entre ele e o jogo estão na **seção 13**.

Fontes da internet usadas:

- Wikipédia, [Southeast Asian mancala](https://en.wikipedia.org/wiki/Southeast_Asian_mancala): tabuleiro de 7 casas, abertura simultânea, os quatro finais de semeadura, rodada seguinte com casas queimadas.
- Ajuda do próprio jogo no BGA, [Gamehelpcongkak](https://en.doc.boardgamearena.com/Gamehelpcongkak): o mesmo conjunto de regras, em resumo.

O congkak tem muitas variações regionais (Malásia, Indonésia, Filipinas, onde se chama sungka). Este jogo foi montado a partir de pesquisa em vídeos e regras de várias regiões, não de um único livro de regras. Escolhas que parecem estranhas, como o sentido da semeadura, são padrões deliberados, não erros.

## 1. O jogo

O congkak é um jogo de semear sementes (da família do mancala), tradicional no Sudeste Asiático. Dois jogadores semeiam sementes pelas casas do tabuleiro e tentam guardar mais sementes no seu armazém que o adversário.

**Jogadores:** exatamente 2.

**Objetivo:** ter mais sementes no armazém (e, conforme o modo, ganhar mais rodadas ou queimar menos casas, seção 10).

## 2. Palavras do jogo

| Termo | Significa |
|---|---|
| **Kampong** | Cada uma das 7 casas pequenas de um jogador. |
| **Rumah** | O armazém de um jogador, a casa grande e oval do lado do tabuleiro. Aqui as sementes capturadas ficam guardadas. |
| **Semear** | Pegar todas as sementes de uma casa e deixar uma em cada casa seguinte. |
| **Casa queimada** | Casa que ficou sem sementes na hora de reabastecer e não entra mais no jogo (seção 8). |

## 3. O que nunca muda e o que cada mesa configura

### O que nunca muda

- 2 jogadores, 7 casas cada um mais um armazém cada um.
- 7 sementes em cada casa no início, 98 sementes no total.
- Sentido horário da semeadura (seção 4).
- As quatro situações do fim de uma semeadura (seção 6).

### O que cada mesa configura

| Opção | Valores | Padrão |
|---|---|---|
| Modo de jogo | Rodada única / Rodadas fixas / Queima total | Rodada única |
| Número de rodadas (só em Rodadas fixas) | 3 / 5 / 7 | 3 |
| Modo de vitória (só em Rodadas fixas) | Número de rodadas / Sementes na última rodada / Casas queimadas | Número de rodadas |
| Casas queimadas (em Rodadas fixas e Queima total) | Desligado / Ligado | Ligado |
| Semeadura automática | Desligado / Ligado | Ligado |

Há ainda uma preferência de cada jogador: **Semeadura automática** (Habilitada / Desabilitada, padrão Desabilitada). Ela vale para o jogador mesmo que a opção da mesa esteja desligada.

"Queima total" só pode ser usado com "Casas queimadas" ligado, e "Casas queimadas" como modo de vitória também.

## 4. Material e preparação

- **Tabuleiro:** duas fileiras de 7 casas e um armazém em cada ponta.
- **Sementes:** 98, em quatro cores que são só enfeite (não têm efeito no jogo).
- **Preparação:** 7 sementes em cada uma das 14 casas, armazéns vazios.
- **Seu lado:** cada jogador vê as suas 7 casas na fileira de baixo, numeradas de 1 a 7 da esquerda para a direita, com o **seu armazém à esquerda**. As casas do adversário ficam em cima, e o armazém dele à direita.
- **Casas opostas:** a casa 1 de um fica em frente à casa 7 do outro, a 2 em frente à 6, e assim por diante (a casa *n* é oposta à casa *8 menos n*).

### Sentido da semeadura

As sementes andam no sentido **horário**. Para um jogador, o caminho é:

1. as suas casas, da 7 para a 1 (da direita para a esquerda);
2. o **seu** armazém;
3. as casas do adversário, da 7 para a 1 (da esquerda para a direita, vistas de quem joga embaixo);
4. de volta à sua casa 7, e assim por diante.

Cada jogador **pula o armazém do adversário**: nunca deixa uma semente lá. Casas queimadas também são puladas (seção 8). **[Decidido em 2026-11-09: horário, confirmado pela pesquisa do André.]**

## 5. Abertura simultânea (só no início da partida)

1. Os dois jogadores escolhem ao mesmo tempo, em segredo, **uma casa do seu lado** com sementes. A escolha de cada um só aparece quando os dois confirmaram.
2. As duas semeaduras acontecem ao mesmo tempo, uma semente de cada jogador por passo. Cada um pega todas as sementes da casa escolhida e semeia a partir da casa seguinte.
3. Quando as duas terminam, cada semeadura é resolvida pelas regras da seção 6, na ordem dos jogadores da mesa. **[Decisão: a ordem pode mudar o resultado, ver seção 12.]**
4. Quem segue jogando depende do que cada um tirou:

| Quantos podem jogar de novo | O que acontece |
|---|---|
| Só um | Esse jogador continua sozinho. Se terminou numa casa com sementes, é **obrigado a semear essa casa** (seção 6, caso 2). Se terminou no armazém, escolhe livremente. Depois os turnos se alternam. |
| Nenhum | O primeiro jogador da ordem da mesa (que o BGA sorteia) joga primeiro. Na **primeira** abertura isso nunca acontece (ver a nota abaixo). |
| Os dois | Os dois continuam ao mesmo tempo, mais uma rodada simultânea (veja abaixo). |

**Ninguém joga de novo.** Na primeira abertura é impossível: todas as casas têm 7 sementes, então a última semente de cada um só cai numa casa vazia se for a casa que o adversário escolheu, e isso só vale para um dos dois ao mesmo tempo. Uma simulação dos 49 pares de escolhas possíveis (feita em 2026-11-09 seguindo a lógica de `SeedingManager`) deu 37 pares em que os dois continuam e 12 em que só um continua, nenhum em que ninguém continua. Nas rodadas simultâneas seguintes (quando os dois continuaram), as casas já têm quantidades diferentes e os dois podem terminar em casa vazia: aí vale a linha "Nenhum" da tabela.

**Os dois continuam ao mesmo tempo:** quem acabou em armazém escolhe livremente uma das suas casas com sementes, mas **não pode escolher a casa de onde o adversário vai continuar**. Quem acabou numa casa com sementes é obrigado a continuar dela. Se os dois ficaram obrigados a continuar **da mesma casa**, não há como semear os dois ao mesmo tempo: o jogo avisa ("Players finished seeding in the same hole") e o primeiro jogador da ordem da mesa joga sozinho, **obrigado a semear essa casa**, como em qualquer final numa casa com sementes (seção 6, caso 2). O outro espera. **[Decidido em 2026-11-09: obrigado, não escolha livre.]**

Depois da abertura, o jogo é **por turnos** até o fim da rodada.

## 6. Como termina uma semeadura

Veja onde caiu a **última** semente:

1. **No seu armazém.** Você joga de novo: escolhe livremente qualquer casa sua com sementes e semeia.
2. **Numa casa que já tinha sementes** (do seu lado ou do adversário). Você pega **todas** as sementes dessa casa (a que acabou de cair incluída) e semeia de novo a partir dela. É obrigatório, não há escolha.
3. **Numa casa vazia do seu lado.** Você **captura**: a semente que caiu e todas as sementes da casa oposta, do adversário, vão para o seu armazém. Se a casa oposta estava vazia, só a sua semente vai para o armazém. O turno acaba.
4. **Numa casa vazia do lado do adversário.** Nada é capturado, a semente fica lá e o turno acaba.

Nos casos 1 e 2 você **continua o mesmo turno**: o turno só passa ao adversário nos casos 3 e 4.

Com a **semeadura automática** (opção da mesa ou preferência do jogador), o jogo semeia sozinho quando só há uma jogada possível, isto é, quando você é obrigado a continuar de uma casa (caso 2).

## 7. Turnos e fim da rodada

- Num turno normal, você escolhe uma das suas casas com sementes e semeia. A escolha tem duas etapas: selecionar a casa (ela fica marcada) e confirmar no botão "Sow".
- Você não pode pegar sementes das casas do adversário, a não ser quando a regra do caso 2 o obriga a continuar de uma delas.
- A rodada **termina quando todas as sete casas de um dos lados ficam vazias**. O jogo confere isso ao fim de cada semeadura.
- As sementes que sobraram nas casas do outro lado vão para o **armazém do dono**.

## 8. Pontuação da rodada e a rodada seguinte

### Quem venceu a rodada

Vence a rodada quem tem mais sementes no armazém. Se empatar, **os dois** ganham uma vitória de rodada. **[Decisão: ver seção 12.]**

Na rodada única o jogo acaba aqui.

### Reabastecer as casas

Nos modos com mais de uma rodada, cada jogador usa as sementes do seu **próprio armazém** para encher de novo as suas casas:

1. Da casa 1 para a 7, põe até **7 sementes** em cada casa.
2. Quando as sementes do armazém acabam no meio de uma casa, essa casa fica com o que tiver (menos de 7) e continua no jogo.
3. As casas seguintes, que não receberam nenhuma semente, ficam **queimadas** (se a opção Casas queimadas estiver ligada). Sobras de um armazém grande demais (mais de 49) ficam no armazém.
4. Com a opção desligada, essas casas ficam vazias, mas continuam no jogo.

### Casas queimadas

- Uma casa queimada **não recebe sementes** (a semeadura a pula), **não pode ser escolhida** e **fica queimada até o fim da partida**. A casa queimada aparece mais clara, com o contador em cinza.
- Quando, num reabastecimento, o jogador chega a uma casa que já estava queimada, as sementes que ainda sobravam no armazém **saem do jogo**. **[Decisão: a internet diz que as sobras voltam ao armazém, ver seção 12.]**
- Sementes que passariam por uma casa queimada **pulam para a próxima casa**. **[Decidido em 2026-11-09: manter, a semeadura pula a casa queimada.]**

### Quem começa a rodada seguinte

Começa quem ficou com **menos sementes no total** (casas e armazém) depois do reabastecimento, ou seja, em geral quem perdeu a rodada. Em empate, o primeiro jogador da ordem da mesa. A conta é feita depois de as sementes retiradas do jogo terem saído, então um jogador que ganhou a rodada mas perdeu sementes no reabastecimento (por ter casas já queimadas) pode acabar com menos e começar. Não há abertura simultânea: só a primeira rodada da partida tem.

## 9. Resumo dos modos de jogo

| Modo | Rodadas | A partida acaba quando |
|---|---|---|
| **Rodada única** | 1 | A rodada acaba (seção 7). Vence quem tem mais sementes no armazém. |
| **Rodadas fixas** | 3, 5 ou 7 | Acabam as rodadas, **ou antes**, quando um jogador já tem **mais da metade** das vitórias de rodada. Pode acabar antes também se um jogador ficar sem casas (veja abaixo). |
| **Queima total** | Sem limite | Um jogador fica com as **7 casas queimadas**, ou com **6 queimadas** e uma sétima que tem **exatamente 1 semente**. |

A condição de "ficar sem casas" (7 queimadas, ou 6 e uma com 1 semente) é conferida logo depois de cada reabastecimento, em qualquer modo que tenha casas queimadas.

## 10. Quem vence a partida

Cada modo de vitória dá uma pontuação a cada jogador, e vence a maior.

| Modo de vitória | Pontuação |
|---|---|
| **Número de rodadas** | Quantas rodadas o jogador venceu. |
| **Sementes na última rodada** | Quantas sementes ele tem no armazém ao fim da última rodada. |
| **Casas queimadas** | 7 menos o número de casas queimadas dele (quem queimou menos, vence). |

- Na **Rodada única**, vale o modo "Número de rodadas": quem tem mais sementes no armazém vence, e um empate dá empate.
- Na **Queima total**, vale sempre "Casas queimadas".
- Nos **Rodadas fixas**, vale o modo escolhido pela mesa.
- Não há critério de desempate além da pontuação: empatados ficam empatados.

## 11. Estatísticas do jogo

Registradas por jogador: sementes no armazém ao fim da partida, número de semeaduras, sementes capturadas ao parar numa casa vazia do próprio lado, semeaduras que acabaram numa casa do adversário, sementes movidas por fim de rodada, rodadas vencidas e média de sementes por rodada.

## 12. Pontos a confirmar com o André

Tudo que o código faz e que o manual acima assume, mas que a internet não confirma, ou confirma de outro jeito. Se a resposta for mudar, é mudança de regra (e o código ou o teste deve mudar junto).

1. **Sentido horário.** **[Decidido em 2026-11-09: horário, confirmado pela pesquisa do André.]** Algumas fontes descrevem o congkak no sentido anti-horário, como o Kalah. Uma variante anti-horária como opção de mesa já foi discutida, não implementada.
2. **Ordem de resolução na abertura.** **[Decidido em 2026-11-09: manter, a ordem da mesa vale como prioridade.]** Cada final de semeadura é resolvido numa ordem fixa (a da mesa), e o segundo jogador já vê o tabuleiro depois do que o primeiro capturou. Quando os dois acabam em casas vazias do próprio lado, quem resolve primeiro pode mudar o que o outro captura.
3. **Abertura em que ninguém joga de novo.** Não acontece na primeira abertura (simulação dos 49 pares, nota na seção 5). Só nas rodadas simultâneas seguintes, e aí o primeiro da ordem da mesa começa. O texto do log só fala em "sorteio" no caso das duas semeaduras terminarem na mesma casa.
4. **Empate de rodada.** **[Decidido em 2026-11-09: manter, os dois ganham.]** Os dois somam uma vitória de rodada. Pode terminar a partida com os dois acima da metade das vitórias. **O manual do André diz o contrário** (empate de rodada: ninguém recebe vitória), ver seção 13.
5. **Sobras quando o reabastecimento chega a uma casa já queimada.** **[Decidido em 2026-11-09: manter.]** Hoje as sementes saem do jogo. A Wikipédia diz que as sobras ficam no armazém, mas o manual do André recomenda retirar do jogo as sementes excedentes, o que bate com o jogo.
6. **Sementes que passam por casa queimada.** **[Decidido em 2026-11-09: manter, a semeadura pula a casa.]** A Wikipédia e a ajuda do BGA dizem que a semente é confiscada e vai para o adversário.
7. **Captura com a casa oposta vazia.** **[Decidido em 2026-11-09: manter.]** Só a semente do jogador vai ao armazém, como na Wikipédia.
8. **Quem começa a rodada seguinte.** Hoje é quem tem menos sementes no total depois do reabastecimento, em empate o primeiro da mesa (seção 8). A fonte diz "o perdedor da rodada anterior começa". A diferença só aparece quando o vencedor da rodada perde sementes no reabastecimento. **[Decidido em 2026-11-09: manter como está por enquanto.]**
9. **Fim da partida por casas queimadas** (7, ou 6 mais uma casa com 1 semente): regra do jogo, sem fonte na internet. **[Decidido em 2026-11-09: manter, sem comparar o depósito.]**
10. **Fim da rodada.** Hoje a rodada acaba quando um lado fica sem sementes nas casas. A Wikipédia diz o mesmo.
11. **Os dois obrigados a continuar da mesma casa na abertura.** **[Decidido em 2026-11-09: o primeiro da mesa joga sozinho e é obrigado a semear essa casa.]** A seção 5 não dizia como ele jogava, e o código dava escolha livre por um erro de gravação (corrigido no mesmo dia).

## 13. O manual do André contra o jogo

Comparação feita em 2026-11-09, ponto a ponto, e respondida pelo André no mesmo dia. O que **não** aparece aqui concorda ou o manual não diz nada (por exemplo, ele deixa o sentido e o percurso da semeadura em aberto, e o jogo é horário). **Regra geral da resposta: onde o manual e o jogo divergem, o jogo vale**, salvo o ponto J, que ficou para depois.

| # | Ponto | Manual do André | Jogo (vale) | Decisão |
|---|---|---|---|---|
| A | Empate de rodada | Ninguém recebe vitória. | Os dois recebem uma vitória de rodada. | **Manter o jogo.** |
| B | Reabastecer | "No máximo uma semente por casa ativa". | Até 7 sementes por casa. | **Manter o jogo.** |
| C | Sementes que sobram | Retiradas do jogo, ou guardadas num "banco". | Saem do jogo só ao chegar a uma casa já queimada. Sem casa queimada no caminho, ficam no armazém. | **Manter o jogo.** |
| D | Queima total | Perde quem fica sem nenhuma casa ativa. Se os dois ficam, compara o depósito, e se empatar é empate. | Acaba com 7 casas queimadas, ou com 6 e a sétima com exatamente 1 semente. Pontua 7 menos casas queimadas, sem comparar o depósito. | **Manter o jogo** (opção 1, decidido em 2026-11-09, ver abaixo). |
| E | Rodadas fixas, soma | "Pontuação acumulada": soma dos depósitos de todas as rodadas. | "Sementes na última rodada" conta só a última. | **Manter o jogo.** |
| F | Desempate no geral | 3 critérios (sementes acumuladas, última rodada, rodada extra ou empate). | Sem desempate. | **Deixar sem desempate.** |
| G | Fim antes do limite de rodadas | Joga o número combinado. | Acaba antes quando um jogador passa da metade das vitórias. | **Manter o jogo.** |
| H | Captura com a oposta vazia | Só fala em captura quando a oposta tem sementes. | Captura sempre, a semente que caiu vai ao armazém. | **Manter o jogo.** |
| I | Abertura simultânea | Pede uma ordem de resolução definida. | Simultânea, com a ordem da mesa como prioridade. | **Manter o jogo.** |
| J | Variações opcionais do manual | Reativar casas, banco de sementes, tempo por decisão, reinício completo, rodada extra. | Nenhuma existe. | **Avaliar depois.** |

### D, decidido: o que o jogo faz e as saídas

Hoje, depois do reabastecimento, o jogo acaba para um jogador quando ele fica com as 7 casas queimadas, ou com 6 queimadas e a sétima com **uma única semente** (uma casa assim quase não joga: semeia 1 semente e pronto). Vence quem tem **menos casas queimadas**. Se os dois ficam sem casas ao mesmo tempo, os dois somam 0 e empatam.

Opções:

1. **Manter como está.** Combina com a decisão do F (sem desempate). O empate dos dois ao mesmo tempo é raro.
2. **Manter e, só nesse caso, comparar o depósito**, como o manual. Dá resultado em vez de empate, mas abre uma exceção ao F.
3. **Seguir o manual à risca:** só 7 queimadas encerra, e sai a regra "6 e 1 semente". A partida ganha uma rodada a mais nesse caso, em que o jogador só tem uma semente solta.

**Decisão de 2026-11-09: opção 1**, manter como está.

Nenhum ponto da seção 12 segue em aberto. O ponto 8 (quem começa a rodada seguinte) foi mantido "por enquanto", então pode voltar a ser discutido.
