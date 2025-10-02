/**
 * andW Moving Letter Marquee JavaScript
 * Handles continuous scrolling of testimonial cards
 */
(function(window, document) {
    'use strict';
    
    class AndwMovingLetterMarquee {
        constructor(container) {
            this.container = container;
            this.rows = [];
            this.config = this.parseConfig();
            this.isInitialized = false;
            this.resizeTimeout = null;
            
            this.init();
        }
        
        parseConfig() {
            const dataset = this.container.dataset;
            
            return {
                visibleDesktop: parseInt(dataset.visibleDesktop) || 5,
                preloadDesktop: parseInt(dataset.preloadDesktop) || 7,
                visibleTablet: parseInt(dataset.visibleTablet) || 3,
                preloadTablet: parseInt(dataset.preloadTablet) || 5,
                visibleMobile: parseInt(dataset.visibleMobile) || 2,
                preloadMobile: parseInt(dataset.preloadMobile) || 4,
                rows: parseInt(dataset.rows) || 3,
                speed: parseInt(dataset.speed) || 50,
                pauseOnHover: dataset.pauseOnHover !== 'false',
                gap: parseInt(dataset.gap) || 20
            };
        }
        
        init() {
            this.setupRows();
            this.duplicateCards();
            this.setupEventListeners();
            this.updateResponsiveSettings();
            this.isInitialized = true;
        }
        
        setupRows() {
            const rowElements = this.container.querySelectorAll('.andw-row');
            
            rowElements.forEach((rowElement, index) => {
                const track = rowElement.querySelector('.andw-track');
                // カードは.andw-cardクラス、または子要素でエレメントノードのみを取得
                const cards = Array.from(track.querySelectorAll('.andw-card')).filter(el => el && el.nodeType === 1);
                
                // カード0枚の場合は行をスキップして安全に処理
                if (cards.length === 0) {
                    console.warn('[andW Moving Letter] Row has no .andw-card elements, skipping row');
                    return;
                }
                
                const direction = rowElement.dataset.direction || (index % 2 === 0 ? 'ltr' : 'rtl');
                
                this.rows.push({
                    element: rowElement,
                    track: track,
                    cards: cards,
                    direction: direction,
                    // 堅牢な複製: 存在確認してからcloneNode実行
                    originalCards: cards.map(card => card && card.cloneNode ? card.cloneNode(true) : null).filter(Boolean)
                });
            });
        }
        
        duplicateCards() {
            this.rows.forEach(row => {
                // 0除算ガード: originalCardsが空または未定義の場合は処理をスキップ
                const base = Array.isArray(row.originalCards) ? row.originalCards : [];
                const baseLen = base.length;
                
                if (baseLen === 0) {
                    console.warn('[andW Moving Letter] No original cards available for duplication, skipping');
                    return;
                }
                
                const cardsToAdd = Math.max(
                    this.getCurrentVisibleCount() + 2,
                    row.cards.length
                );
                
                // 現在のカードを複製して追加（intdiv的な0除算回避でbaseLen使用）
                for (let i = 0; i < cardsToAdd; i++) {
                    const originalCard = base[i % baseLen]; // baseLenは必ず1以上
                    if (originalCard && originalCard.cloneNode) {
                        const duplicatedCard = originalCard.cloneNode(true);
                        duplicatedCard.classList.add('ml-card-duplicate');
                        row.track.appendChild(duplicatedCard);
                    }
                }
                
                // カード配列を更新（エレメントノードのみフィルタ）
                row.cards = Array.from(row.track.children).filter(n => n.nodeType === 1);
            });
        }
        
        getCurrentVisibleCount() {
            const width = window.innerWidth;
            
            if (width <= 480) {
                return this.config.visibleMobile;
            } else if (width <= 768) {
                return this.config.visibleTablet;
            } else {
                return this.config.visibleDesktop;
            }
        }
        
        getCurrentPreloadCount() {
            const width = window.innerWidth;
            
            if (width <= 480) {
                return this.config.preloadMobile;
            } else if (width <= 768) {
                return this.config.preloadTablet;
            } else {
                return this.config.preloadDesktop;
            }
        }
        
        updateResponsiveSettings() {
            const visibleCount = this.getCurrentVisibleCount();
            const root = document.documentElement;
            
            // CSS カスタムプロパティを更新
            root.style.setProperty('--andw-visible-cards', visibleCount);
            root.style.setProperty('--andw-gap', this.config.gap + 'px');
            root.style.setProperty('--andw-speed', this.config.speed + 's');
            
            // アニメーション速度をレスポンシブに調整
            const speedMultiplier = this.getSpeedMultiplier();
            const adjustedSpeed = this.config.speed * speedMultiplier;
            root.style.setProperty('--andw-speed', adjustedSpeed + 's');
        }
        
        getSpeedMultiplier() {
            const width = window.innerWidth;
            
            if (width <= 480) {
                return 0.7; // モバイルは少し遅く
            } else if (width <= 768) {
                return 0.85; // タブレットは少し遅く
            } else {
                return 1; // デスクトップは標準速度
            }
        }
        
        setupEventListeners() {
            // ホバー時の一時停止
            if (this.config.pauseOnHover) {
                this.container.addEventListener('mouseenter', () => {
                    this.pauseAnimation();
                });
                
                this.container.addEventListener('mouseleave', () => {
                    this.resumeAnimation();
                });
            }
            
            // リサイズ対応
            window.addEventListener('resize', () => {
                clearTimeout(this.resizeTimeout);
                this.resizeTimeout = setTimeout(() => {
                    this.handleResize();
                }, 250);
            });
            
            // ページの可視性変更対応
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.pauseAnimation();
                } else {
                    this.resumeAnimation();
                }
            });
            
            // タッチデバイスでのスクロール中はアニメーションを一時停止
            let touchTimeout;
            window.addEventListener('touchstart', () => {
                this.pauseAnimation();
                clearTimeout(touchTimeout);
            });
            
            window.addEventListener('touchend', () => {
                touchTimeout = setTimeout(() => {
                    this.resumeAnimation();
                }, 1000);
            });
        }
        
        handleResize() {
            const newVisibleCount = this.getCurrentVisibleCount();
            const oldVisibleCount = parseInt(
                getComputedStyle(document.documentElement)
                    .getPropertyValue('--andw-visible-cards')
            );
            
            if (newVisibleCount !== oldVisibleCount) {
                this.updateResponsiveSettings();
                this.recalculateCards();
            }
        }
        
        recalculateCards() {
            this.rows.forEach(row => {
                // 0除算ガード: originalCardsの存在確認
                const base = Array.isArray(row.originalCards) ? row.originalCards : [];
                const baseLen = base.length;
                
                if (baseLen === 0) {
                    console.warn('[andW Moving Letter] No original cards for recalculation, skipping');
                    return;
                }
                
                const currentVisibleCount = this.getCurrentVisibleCount();
                const currentCardCount = row.cards ? row.cards.length : 0;
                const requiredCards = Math.max(currentVisibleCount + 4, 8);
                
                if (currentCardCount < requiredCards) {
                    // カードを追加（同様にbaseLen使用で0除算回避）
                    const cardsToAdd = requiredCards - currentCardCount;
                    for (let i = 0; i < cardsToAdd; i++) {
                        const originalCard = base[i % baseLen]; // baseLenは必ず1以上
                        if (originalCard && originalCard.cloneNode) {
                            const duplicatedCard = originalCard.cloneNode(true);
                            duplicatedCard.classList.add('ml-card-duplicate');
                            row.track.appendChild(duplicatedCard);
                        }
                    }
                    // エレメントノードのみフィルタして更新
                    row.cards = Array.from(row.track.children).filter(n => n.nodeType === 1);
                }
            });
        }
        
        pauseAnimation() {
            this.rows.forEach(row => {
                row.track.style.animationPlayState = 'paused';
            });
        }
        
        resumeAnimation() {
            this.rows.forEach(row => {
                row.track.style.animationPlayState = 'running';
            });
        }
        
        destroy() {
            // イベントリスナーを削除
            window.removeEventListener('resize', this.handleResize);
            
            // アニメーションを停止
            this.rows.forEach(row => {
                row.track.style.animation = 'none';
            });
            
            this.isInitialized = false;
        }
        
        // パフォーマンス最適化
        optimizePerformance() {
            // Intersection Observer を使用して見えていない行のアニメーションを停止
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        const row = this.rows.find(r => r.element === entry.target);
                        if (row) {
                            if (entry.isIntersecting) {
                                row.track.style.animationPlayState = 'running';
                            } else {
                                row.track.style.animationPlayState = 'paused';
                            }
                        }
                    });
                }, {
                    rootMargin: '50px'
                });
                
                this.rows.forEach(row => {
                    observer.observe(row.element);
                });
            }
        }
        
        // アクセシビリティ対応
        setupAccessibility() {
            // prefers-reduced-motion対応
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            
            if (prefersReducedMotion) {
                this.rows.forEach(row => {
                    row.track.style.animation = 'none';
                });
                return;
            }
            
            // スクリーンリーダー用の説明を追加
            this.container.setAttribute('aria-label', 'お客様の声のスライドショー');
            this.container.setAttribute('role', 'region');
            
            // カードにタブインデックスを設定
            this.rows.forEach(row => {
                row.cards.forEach((card, index) => {
                    card.setAttribute('tabindex', index < this.getCurrentVisibleCount() ? '0' : '-1');
                });
            });
        }
    }
    
    // グローバルに公開
    window.AndwMovingLetterMarquee = AndwMovingLetterMarquee;
    
    // 自動初期化
    document.addEventListener('DOMContentLoaded', function() {
        const containers = document.querySelectorAll('.andw-container');
        containers.forEach(function(container) {
            new AndwMovingLetterMarquee(container);
        });
    });
    
})(window, document);