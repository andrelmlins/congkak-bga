const delayTime = (time: number) => new Promise<void>((resolve: VoidFunction) => setTimeout(() => resolve(), time));
