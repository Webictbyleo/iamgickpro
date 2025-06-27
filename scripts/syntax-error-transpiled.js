return value;
  };
  class DesignRenderer {
    constructor() {
      this.stage = null;
      this.layer = null;
      this.renderers = /* @__PURE__ */ new Map(;);
      this.initializeRenderers();
      this.createOffscreenStage();
    }
    /**
     * Initialize all layer renderers
     */
    initializeRenderers() {
      this.renderers.set("text", new TextLayerRenderer());
      this.renderers.set("image", new ImageLayerRenderer());
      this.renderers.set("shape", new ShapeLayerRenderer());
      this.renderers.set("svg", new SVGLayerRenderer());
      this.renderers.set("group", new GroupLayerRenderer());
    }
    /**
     * Render a design to a canvas element
     */
    async renderToCanvas(design, options = {}) {
      if (!this.stage || !this.layer) {
        throw new Error("DesignRenderer not properly initialized");
      }
      const {
        width = design.width,
        height = design.height,
        scale = 1,
        pixelRatio = 1,
        // Renamed from 'quality' to be more accurate
        background = design.data?.backgroundColor || design.data?.background?.color || "#ffffff"
      } = options;
      this.stage.width(width * scale);
      this.stage.height(height * scale);
      this.stage.scale({ x: scale, y: scale });
      this.layer.destroyChildren();
      const backgroundRect = new global.Konva.Rect({
        x: 0,
        y: 0,
        width: design.width,
        height: design.height,
        listening: false
      });
      const backgroundConfig = design.data?.background || { type: "solid", color: background };
      this.applyBackgroundToRect(backgroundRect, backgroundConfig, design.width, design.height);
      this.layer.add(backgroundRect);
      if (design.layers && design.layers.length > 0) {
        await this.renderLayersWithRenderers(design.layers);
      }
      return this.stage.toCanvas({
        pixelRatio
      });
    }
    /**
     * Render layers using the existing renderer system
     */
    async renderLayersWithRenderers(layers) {
      if (!this.layer)
        return;
      const sortedLayers = [...layers].sort((a, b) => (a.zIndex || 0) - (b.zIndex || 0));
      for (const layer of sortedLayers) {
        if (!layer.visible)
          continue;
        const renderer = this.renderers.get(layer.type);
        if (!renderer) {
          console.warn(`No renderer found for layer type: ${layer.type}`);
          continue;
        }
        try {
          const layerNode = {
            id: layer.id,
            type: layer.type,
            name: layer.name,
            x: layer.transform?.x || 0,
            y: layer.transform?.y || 0,
            width: layer.transform?.width || 100,
            height: layer.transform?.height || 100,
            rotation: layer.transform?.rotation || 0,
            scaleX: layer.transform?.scaleX || 1,
            scaleY: layer.transform?.scaleY || 1,
            opacity: layer.transform?.opacity !== void 0 ? layer.transform.opacity : 1,
            visible: layer.visible,
            locked: layer.locked || false,
            zIndex: layer.zIndex || 0,
            properties: layer.properties || {},
            konvaNode: void 0
            // Will be set by renderer
          };
          const konvaNode = await this.renderLayerNode(layerNode, renderer);
          if (konvaNode) {
            this.layer.add(konvaNode);
          }
        } catch (error) {
          console.error(`Failed to render layer ${layer.id}:`, error);
        }
      }
    }
    /**
     * Render a single layer node using the appropriate renderer
     */
    async renderLayerNode(layerNode, renderer) {
      try {
        const konvaNode = renderer.render(layerNode);
        if (konvaNode) {
          konvaNode.setAttrs({
            x: layerNode.x,
            y: layerNode.y,
            rotation: layerNode.rotation,
            scaleX: layerNode.scaleX,
            scaleY: layerNode.scaleY,
            opacity: layerNode.opacity,
            visible: layerNode.visible
          });
          if (typeof renderer.update === "function") {
            renderer.update(konvaNode, layerNode);
          }
          if (layerNode.type === "image" && konvaNode instanceof global.Konva.Group) {
            await this.waitForImageLoad(konvaNode);
          }
        }
        return konvaNode;
      } catch (error) {
        console.error(`Renderer failed for layer ${layerNode.id}:`, error);
        return null;
      }
    }
    /**
     * Wait for image layers to load before continuing
     */
    async waitForImageLoad(group) {
      return new Promise((resolve) => {
        const checkImage = /* @__PURE__ */ __name(() => {
          const imageNode = group.findOne("Image");
          if (imageNode && imageNode.image()) {
            resolve();
          } else {
            setTimeout(checkImage, 50);
          }
        }, "checkImage");
        checkImage();
      });
    }
    /**
     * Generate a thumbnail as base64 data URL
     */
    async generateThumbnail(design, options = {}) {
      const {
        width = 300,
        height = 200,
        format = "png",
        quality = 0.8,
        pixelRatio = 1,
        background = design.data?.backgroundColor || design.data?.background?.color || "#ffffff"
      } = options;
      const numericQuality = typeof quality === "string" ? parseFloat(quality) : quality;
      const scaleX = width / design.width;
      const scaleY = height / design.height;
      const scale = Math.min(scaleX, scaleY);
      const canvas = await this.renderToCanvas(design, {
        width: design.width,
        height: design.height,
        scale,
        pixelRatio,
        background
      });
      const thumbnailCanvas = createCanvas();
      thumbnailCanvas.width = width;
      thumbnailCanvas.height = height;
      const ctx = thumbnailCanvas.getContext("2d");
      const backgroundConfig = design.data?.background || { type: "solid", color: background };
      this.applyBackgroundToCanvas(ctx, backgroundConfig, width, height);
      const scaledWidth = design.width * scale;
      const scaledHeight = design.height * scale;
      const offsetX = (width - scaledWidth) / 2;
      const offsetY = (height - scaledHeight) / 2;
      ctx.drawImage(canvas, offsetX, offsetY, scaledWidth, scaledHeight);
      return thumbnailCanvas.toDataURL(`image/${format}`, numericQuality);
    }
    /**
     * Generate a preview image as blob
     */
    async generatePreviewBlob(design, options = {}) {
      return new Promise(async (resolve, reject) => {
        try {
          const {
            width,
            height,
            format = "png",
            quality = 0.8,
            pixelRatio = 1,
            background
          } = options;
          const numericQuality = typeof quality === "string" ? parseFloat(quality) : quality;
          const canvas = await this.renderToCanvas(design, {
            width,
            height,
            pixelRatio,
            background
          });
          canvas.toBlob((blob) => {
            if (blob) {
              resolve(blob);
            } else {
              reject(new Error("Failed to generate preview blob"));
            }
          }, `image/${format}`, numericQuality);
        } catch (error) {
          reject(error);
        }
      });
    }
    /**
     * Generate multiple preview sizes at once
     */
    async generateMultiplePreviews(design, sizes) {
      const previews = {};
      for (const size of sizes) {
        try {
          const thumbnail = await this.generateThumbnail(design, {
            width: size.width,
            height: size.height,
            format: size.format || "png",
            quality: size.quality || 0.8,
            pixelRatio: size.pixelRatio || 1
          });
          previews[size.name] = thumbnail;
        } catch (error) {
          console.error(`Failed to generate preview for size ${size.name}:`, error);
        }
      }
      return previews;
    }
    /**
     * Export design to various formats with proper quality control
     */
    async exportDesign(design, options = {}) {
      const {
        width = design.width,
        height = design.height,
        scale = 1,
        format = "png",
        quality = 0.92,
        // High quality by default
        pixelRatio = 1,
        background = design.data?.backgroundColor || design.data?.background?.color || "#ffffff"
      } = options;
      const numericQuality = typeof quality === "string" ? parseFloat(quality) : quality;
      const canvas = await this.renderToCanvas(design, {
        width,
        height,
        scale,
        pixelRatio,
        background
      });
      const mimeType = `image/${format}`;
      const dataUrl = canvas.toDataURL(mimeType, numericQuality);
      const blob = await new Promise((resolve, reject) => {
        canvas.toBlob((blob2) => {
          if (blob2) {
            resolve(blob2);
          } else {
            reject(new Error(`Failed to generate ${format.toUpperCase()} blob`));
          }
        }, mimeType, numericQuality);
      });
      return {
        canvas,
        dataUrl,
        blob
      };
    }
    /**
     * Create an off-screen stage for rendering
     */
    createOffscreenStage() {
      this.stage = new global.global.Konva.Stage({
        container: null,
        width: 800,
        height: 600
      });
      this.layer = new global.global.Konva.Layer();
      this.stage.add(this.layer);
    }
    /**
     * Clean up resources
     */
    destroy() {
      if (this.stage) {
        const container = this.stage.container();
        this.stage.destroy();
        if (container && container.parentNode) {
          mockRemoveChild(container);
        }
        this.stage = null;
        this.layer = null;
      }
    }
    /**
     * Apply background configuration to a Konva rectangle
     */
    applyBackgroundToRect(rect, backgroundConfig, width, height) {
      if (!backgroundConfig) {
        rect.fill("#ffffff");
        return;
      }
      if (backgroundConfig.type === "solid" || !backgroundConfig.type) {
        rect.fill(backgroundConfig.color || "#ffffff");
        rect.fillLinearGradientStartPoint(void 0);
        rect.fillLinearGradientEndPoint(void 0);
        rect.fillRadialGradientStartPoint(void 0);
        rect.fillRadialGradientEndPoint(void 0);
      } else if (backgroundConfig.type === "linear" && backgroundConfig.gradient) {
        const gradient = backgroundConfig.gradient;
        const angle = gradient.angle || 0;
        const radians = angle * Math.PI / 180;
        const length = Math.sqrt(width * width + height * height);
        const centerX = width / 2;
        const centerY = height / 2;
        const startX = centerX - Math.cos(radians) * length / 2;
        const startY = centerY - Math.sin(radians) * length / 2;
        const endX = centerX + Math.cos(radians) * length / 2;
        const endY = centerY + Math.sin(radians) * length / 2;
        const colorStops = [];
        gradient.colors.forEach((stop) => {
          colorStops.push(stop.stop, stop.color);
        });
        rect.fill(void 0);
        rect.fillLinearGradientStartPoint({ x: startX, y: startY });
        rect.fillLinearGradientEndPoint({ x: endX, y: endY });
        rect.fillLinearGradientColorStops(colorStops);
        rect.fillRadialGradientStartPoint(void 0);
        rect.fillRadialGradientEndPoint(void 0);
      } else if (backgroundConfig.type === "radial" && backgroundConfig.gradient) {
        const gradient = backgroundConfig.gradient;
        const centerX = (gradient.centerX || 0.5) * width;
        const centerY = (gradient.centerY || 0.5) * height;
        const radius = (gradient.radius || 0.7) * Math.max(width, height) / 2;
        const colorStops = [];
        gradient.colors.forEach((stop) => {
          colorStops.push(stop.stop, stop.color);
        });
        rect.fill(void 0);
        rect.fillRadialGradientStartPoint({ x: centerX, y: centerY });
        rect.fillRadialGradientEndPoint({ x: centerX, y: centerY });
        rect.fillRadialGradientStartRadius(0);
        rect.fillRadialGradientEndRadius(radius);
        rect.fillRadialGradientColorStops(colorStops);
        rect.fillLinearGradientStartPoint(void 0);
        rect.fillLinearGradientEndPoint(void 0);
      } else {
        rect.fill(backgroundConfig.color || "#ffffff");
      }
    }
    /**
     * Apply background to a canvas context (for thumbnail generation)
     */
    applyBackgroundToCanvas(ctx, backgroundConfig, width, height) {
      if (!backgroundConfig) {
        ctx.fillStyle = "#ffffff";
        ctx.fillRect(0, 0, width, height);
        return;
      }
      if (backgroundConfig.type === "solid" || !backgroundConfig.type) {
        ctx.fillStyle = backgroundConfig.color || "#ffffff";
        ctx.fillRect(0, 0, width, height);
      } else if (backgroundConfig.type === "linear" && backgroundConfig.gradient) {
        const gradient = backgroundConfig.gradient;
        const angle = gradient.angle || 0;
        const radians = angle * Math.PI / 180;
        const length = Math.sqrt(width * width + height * height);
        const centerX = width / 2;
        const centerY = height / 2;
        const startX = centerX - Math.cos(radians) * length / 2;
        const startY = centerY - Math.sin(radians) * length / 2;
        const endX = centerX + Math.cos(radians) * length / 2;
        const endY = centerY + Math.sin(radians) * length / 2;
        const canvasGradient = ctx.createLinearGradient(startX, startY, endX, endY);
        gradient.colors.forEach((stop) => {
          canvasGradient.addColorStop(stop.stop, stop.color);
        });
        ctx.fillStyle = canvasGradient;
        ctx.fillRect(0, 0, width, height);
      } else if (backgroundConfig.type === "radial" && backgroundConfig.gradient) {
        const gradient = backgroundConfig.gradient;
        const centerX = (gradient.centerX || 0.5) * width;
        const centerY = (gradient.centerY || 0.5) * height;
        const radius = (gradient.radius || 0.7) * Math.max(width, height) / 2;
        const canvasGradient = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, radius);
        gradient.colors.forEach((stop) => {
          canvasGradient.addColorStop(stop.stop, stop.color);
        });
        ctx.fillStyle = canvasGradient;
        ctx.fillRect(0, 0, width, height);
      } else {
        ctx.fillStyle = backgroundConfig.color || "#ffffff";
        ctx.fillRect(0, 0, width, height);
      }
    }
  };
  __name(DesignRenderer, "DesignRenderer");
  let DesignRenderer = DesignRenderer;
  const designRenderer = new DesignRenderer();