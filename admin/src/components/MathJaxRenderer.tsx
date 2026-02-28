import React from 'react';
import { MathJaxContext, MathJax } from 'better-react-mathjax';

interface MathJaxRendererProps {
  children: React.ReactNode;
  className?: string;
}

/**
 * MathJax Renderer Component
 * Simplified version for better compatibility
 */

// Simplified MathJax configuration
const mathJaxConfig = {
  loader: { load: ['input/tex', 'output/chtml'] },
  tex: {
    inlineMath: [
      ['$', '$'],
      ['\\(', '\\)']
    ],
    displayMath: [
      ['$$', '$$'],
      ['\\[', '\\]']
    ],
    processEscapes: true,
    processEnvironments: true
  },
  options: {
    skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre']
  }
};

const MathJaxRenderer: React.FC<MathJaxRendererProps> = ({ children, className }) => {
  // Simple preprocessing for common issues
  const preprocessText = (text: string): string => {
    if (!text || typeof text !== 'string') {
      return text;
    }

    let processedText = text;

    // Handle newline escape sequences
    processedText = processedText.replace(/\\n/g, '\n');

    // Handle double backslashes first
    processedText = processedText.replace(/\\\\([a-zA-Z]+)/g, '\\$1');

    // Auto-wrap common patterns if no math delimiters exist
    if (!text.includes('$') && !text.includes('\\(') && !text.includes('\\[')) {
      
      // Check for multiple choice chemistry questions with options (A), (B), (C), (D)
      if (processedText.includes('(A)') && processedText.includes('(B)') && 
          (processedText.includes('\\Delta H') || processedText.includes('\\leftrightarrow') || processedText.includes('_'))) {
        // Split by options and process each line separately
        const lines = processedText.split(/\n+/);
        const processedLines = lines.map(line => {
          if (line.trim().match(/^\([A-D]\)/)) {
            // This is an option line with chemistry - wrap the chemistry part in math delimiters
            return line.replace(/(\([A-D]\)\s*)(.+)/, (match, option, chemistry) => {
              if (chemistry.includes('\\') || chemistry.includes('_') || chemistry.includes('^')) {
                return `${option}$$${chemistry}$$`;
              }
              return match;
            });
          } else if (line.includes('\\') || line.includes('_') || line.includes('^')) {
            // Regular line with LaTeX - wrap if it contains LaTeX syntax
            return `$$${line}$$`;
          }
          return line;
        });
        return processedLines.join('\n\n');
      }
      // Check for complex chemical equations with fractions and arrows
      else if (processedText.includes('\\frac') && (processedText.includes('\\rightleftharpoons') || processedText.includes('\\rightarrow'))) {
        // Wrap the entire equation in math delimiters
        processedText = `$$${processedText}$$`;
      }
      // Check for equations with multiple chemical compounds and arrows
      else if (processedText.match(/[A-Z][a-z]?_\d+.*\\(rightleftharpoons|rightarrow|leftarrow)/)) {
        processedText = `$$${processedText}$$`;
      }
      // Individual pattern wrapping (for simpler cases)
      else {
        // Thermodynamic expressions (Delta H, Delta G, etc.)
        processedText = processedText.replace(/\\Delta\s*[HGS]\^?\\circ?\s*=\s*[^,\n]+/g, '$$&$$');
        
        // Chemical formulas with subscripts
        processedText = processedText.replace(/\b([A-Z][a-z]?)(_\d+)+(\([gs]\))?/g, '$$$1$2$3$$');
        
        // Variables with subscripts (K_p, etc.)
        processedText = processedText.replace(/\b([A-Za-z])(_[A-Za-z0-9]+)/g, '$$$1$2$$');
        
        // Greek letters
        processedText = processedText.replace(/\\(alpha|beta|gamma|delta|epsilon|zeta|eta|theta|iota|kappa|lambda|mu|nu|xi|omicron|pi|rho|sigma|tau|upsilon|phi|chi|psi|omega)/g, '$\\$1$');
        
        // Fractions (only if not already wrapped)
        processedText = processedText.replace(/\\frac\{[^}]+\}\{[^}]+\}/g, '$$&$$');
        
        // Arrows
        processedText = processedText.replace(/\\(rightarrow|leftarrow|leftrightarrow|rightleftharpoons)/g, '$\\$1$');
        
        // Text commands in LaTeX (\text{...})
        processedText = processedText.replace(/\\text\{[^}]+\}/g, '$$&$$');
      }
    }

    return processedText;
  };

  const renderContent = () => {
    if (typeof children === 'string') {
      const processedText = preprocessText(children);
      return processedText;
    }
    return children;
  };

  return (
    <span className={className}>
      <MathJax>
        {renderContent()}
      </MathJax>
    </span>
  );
};

/**
 * MathJax Provider Component
 * Wrap your app or component tree with this to enable MathJax rendering
 */
export const MathJaxProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  return (
    <MathJaxContext config={mathJaxConfig}>
      {children}
    </MathJaxContext>
  );
};

export default MathJaxRenderer;