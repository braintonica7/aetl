import React from 'react';
import 'katex/dist/katex.min.css';
import { InlineMath, BlockMath } from 'react-katex';

interface LaTeXRendererProps {
  children: React.ReactNode;
  className?: string;
}

/**
 * LaTeX Renderer Component
 * Automatically detects and renders LaTeX expressions in text
 * - Inline math: $expression$ or \(expression\)
 * - Display math: $$expression$$ or \[expression\]
 */
const LaTeXRenderer: React.FC<LaTeXRendererProps> = ({ children, className }) => {
  
  /**
   * Auto-wrap common LaTeX expressions that aren't already wrapped
   */
  const autoWrapLatexExpressions = (text: string): string => {
    // Don't process text that already has LaTeX delimiters
    if (text.includes('$') || text.includes('\\(') || text.includes('\\[')) {
      return text;
    }

    // First, normalize double backslashes to single backslashes for LaTeX commands
    let processedText = text.replace(/\\\\([a-zA-Z]+)/g, '\\$1');

    // Patterns for common LaTeX expressions
    const patterns = [
      // Chemical formulas with subscripts (H_2O, CO_2, etc.)
      {
        regex: /\b([A-Z][a-z]?)(_\d+)+(\([gs]\))?/g,
        wrapper: '$'
      },
      // Mathematical expressions with subscripts/superscripts
      {
        regex: /\b[A-Za-z](_[A-Za-z0-9]+|\^[A-Za-z0-9]+)/g,
        wrapper: '$'
      },
      // Fractions (\frac{}{})
      {
        regex: /\\frac\{[^}]+\}\{[^}]+\}/g,
        wrapper: '$'
      },
      // Greek letters (including both single and double backslash variants)
      {
        regex: /\\\\?(alpha|beta|gamma|delta|epsilon|zeta|eta|theta|iota|kappa|lambda|mu|nu|xi|omicron|pi|rho|sigma|tau|upsilon|phi|chi|psi|omega|Alpha|Beta|Gamma|Delta|Epsilon|Zeta|Eta|Theta|Iota|Kappa|Lambda|Mu|Nu|Xi|Omicron|Pi|Rho|Sigma|Tau|Upsilon|Phi|Chi|Psi|Omega)/g,
        wrapper: '$',
        normalize: true // Flag to normalize double backslashes
      },
      // Arrows and reactions
      {
        regex: /\\\\?(rightarrow|leftarrow|leftrightarrow|Rightarrow|Leftarrow|Leftrightarrow|rightleftharpoons)/g,
        wrapper: '$',
        normalize: true
      },
      // Mathematical operators and symbols
      {
        regex: /\\\\?(times|div|pm|mp|cdot|sum|prod|int|partial|nabla|infty|sqrt|text)/g,
        wrapper: '$',
        normalize: true
      },
      // Complete chemical equations (more complex pattern)
      {
        regex: /([A-Z][a-z]?(_\d+)*(\([gs]\))?(\s*\+\s*[A-Z][a-z]?(_\d+)*(\([gs]\))?)*)\s*\\\\?rightleftharpoons\s*([A-Z][a-z]?(_\d+)*(\([gs]\))?(\s*\+\s*[A-Z][a-z]?(_\d+)*(\([gs]\))?)*)/g,
        wrapper: '$',
        normalize: true
      }
    ];

    // Start with normalized text (fix double backslashes)
    processedText = processedText.replace(/\\\\([a-zA-Z]+)/g, '\\$1');
    
    patterns.forEach(pattern => {
      processedText = processedText.replace(pattern.regex, (match) => {
        // Normalize the match if needed (convert double backslashes to single)
        let normalizedMatch = match;
        if (pattern.normalize) {
          normalizedMatch = match.replace(/\\\\([a-zA-Z]+)/g, '\\$1');
        }
        
        // Check if this match is already wrapped
        const beforeMatch = processedText.substring(0, processedText.indexOf(match));
        const afterMatch = processedText.substring(processedText.indexOf(match) + match.length);
        
        // Don't wrap if already inside LaTeX delimiters
        if (beforeMatch.endsWith('$') || afterMatch.startsWith('$')) {
          return normalizedMatch;
        }
        
        return `${pattern.wrapper}${normalizedMatch}${pattern.wrapper}`;
      });
    });

    return processedText;
  };

  const renderLatex = (text: string) => {
    // Ensure text is a string
    if (!text || typeof text !== 'string') {
      return text;
    }

    // First, auto-wrap common LaTeX patterns that aren't already wrapped
    let processedText = autoWrapLatexExpressions(text);

    // Handle display math first ($$...$$)
    const displayMathRegex = /\$\$(.*?)\$\$/g;
    let parts = processedText.split(displayMathRegex);
    let result: React.ReactNode[] = [];
    
    for (let i = 0; i < parts.length; i++) {
      if (i % 2 === 0) {
        // Regular text or inline math
        const textPart = parts[i];
        if (textPart) {
          result.push(...renderInlineMath(textPart, i));
        }
      } else {
        // Display math
        const mathExpression = parts[i];
        if (mathExpression) {
          try {
            result.push(
              <div key={`display-${i}`} style={{ margin: '16px 0', textAlign: 'center' }}>
                <BlockMath>{mathExpression}</BlockMath>
              </div>
            );
          } catch (error) {
            console.warn('LaTeX display math rendering error:', error);
            result.push(
              <div key={`display-error-${i}`} style={{ 
                color: 'red', 
                fontStyle: 'italic',
                margin: '16px 0',
                textAlign: 'center'
              }}>
                [LaTeX Error: {mathExpression}]
              </div>
            );
          }
        }
      }
    }
    
    return result;
  };

  const renderInlineMath = (text: string, baseKey: number = 0) => {
    // Ensure text is a string
    if (!text || typeof text !== 'string') {
      return [<span key={`text-${baseKey}-fallback`}>{text}</span>];
    }

    // Handle inline math with $ or \( \)
    const inlineMathRegex = /\$([^$]+)\$|\\\((.*?)\\\)/g;
    const parts = text.split(inlineMathRegex);
    const result: React.ReactNode[] = [];
    
    for (let i = 0; i < parts.length; i++) {
      if (parts[i] === undefined) continue;
      
      if (i % 3 === 0) {
        // Regular text
        if (parts[i]) {
          result.push(<span key={`text-${baseKey}-${i}`}>{parts[i]}</span>);
        }
      } else if (i % 3 === 1 || i % 3 === 2) {
        // Math expression (either $...$ or \(...\))
        const mathExpression = parts[i];
        if (mathExpression) {
          try {
            result.push(
              <InlineMath key={`inline-${baseKey}-${i}`}>
                {mathExpression}
              </InlineMath>
            );
          } catch (error) {
            console.warn('LaTeX inline math rendering error:', error);
            result.push(
              <span key={`inline-error-${baseKey}-${i}`} style={{ 
                color: 'red', 
                fontStyle: 'italic' 
              }}>
                [LaTeX Error: {mathExpression}]
              </span>
            );
          }
        }
      }
    }
    
    return result;
  };

  return (
    <span className={className}>
      {typeof children === 'string' ? renderLatex(children) : children}
    </span>
  );
};

export default LaTeXRenderer;