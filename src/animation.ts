class BgaLocalAnimation {
  private origin: HTMLElement;
  private destination: HTMLElement;
  private duration: number;
  private where: string = 'beforeend';
  private rotation: number = 0;
  private scale: number;

  constructor(public game: CongkakGame) {}

  public setOptions(origin: HTMLElement, destination: HTMLElement, duration: number) {
    this.origin = origin;
    this.destination = destination;
    this.duration = duration;
  }

  public setWhere(where: InsertPosition) {
    this.where = where;
  }

  public setRotation(rotation: number) {
    this.rotation = rotation;
  }

  public setScale(scale: number) {
    this.scale = scale;
  }

  public call(handle: (node: HTMLElement) => void, handlePreAnim?: (node: HTMLElement) => void): Promise<void> {
    return new Promise(async (resolve, _) => {
      if (!this.isUsed()) {
        handlePreAnim?.call(null);
        handle(this.origin);
        resolve();

        return;
      }

      let animation = new BgaSlideAnimation({
        element: this.origin,
        duration: this.duration,
        rotationDelta: this.rotation,
        scale: this.scale,
      });

      await this.game.animationManager.play(
        new BgaAttachWithAnimation({
          animation,
          where: this.where,
          attachElement: this.destination,
          afterAttach: (element: HTMLElement) => handlePreAnim?.call(null, element),
        })
      );

      handle(this.origin);
      resolve();
    });
  }

  private isUsed() {
    return (this.game as any).getGameUserPreference(100) == 1;
  }
}
