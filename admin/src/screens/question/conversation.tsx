import React, { useState } from 'react';
import { useConversation } from '@elevenlabs/react';
import { useCallback } from 'react';
import './conversation.css';

export function Conversation(props: any) {
  console.log("Conversation : ", props);
  const conversation = useConversation({
    onConnect: () => {
      console.log('Connected to conversation');
      // Send a contextual update to the agent
      // const context = `You are a helpful assistant that answers questions about math and science. 
      //   you need to explain the following solution to user: ${props.solution}.`
      // console.log('Sending contextual update:', context);
      // conversation.sendContextualUpdate(
      //   context
      // );
      const userMessage = `Please explain the following solution to user: ${props.solution}`;
      console.log('Sending user message:', userMessage);
      // Send a user message to the agent
      conversation.sendUserMessage(
        userMessage
      );
    },
    onDisconnect: () => console.log('Disconnected'),
    onMessage: (message) => console.log('Message:', message),
    onError: (error) => console.error('Error:', error),
  });


  const startConversation = useCallback(async () => {
    try {
      // Request microphone permission
      await navigator.mediaDevices.getUserMedia({ audio: true });

      // Start the conversation with your agent
      const userMessage: any = `Please explain the following solution to user: ${props.solution}`;
      await conversation.startSession({
        agentId: 'agent_01k02154kmfbxrzsssr0xsp6fv', 
        connectionType: 'websocket',
      });
      
      // Send the context to agent that he needs to stick on this solution only no other topic to discuss, if user ask any other topic then politly say that you need to connect on Learning Library for that. At the end of conversation , please say what topic user needs to improve based on the discussion.
      // console.log('Sending contextual update:', context);
      conversation.sendContextualUpdate(
        `You are a helpful assistant that answers questions about math and science.
          you need to explain the following solution to user: ${props.solution}.
          If user asks any other topic, politely say that you need to connect on Learning Library for that. 
          At the end of conversation, please say what topic user needs to improve based on the discussion. 
          `)
      // Send the initial message to the agent
      console.log('Conversation started with agent:', userMessage);
      setTimeout(() => {
        conversation.sendUserMessage(userMessage);
      }, 100);


    } catch (error) {
      console.error('Failed to start conversation:', error);
    }
  }, [conversation]);

  const stopConversation = useCallback(async () => {
    await conversation.endSession();
  }, [conversation]);

  return (
    <div className="conversation-container">
      {/* Animated Conversation Button */}
      <div className="conversation-button-wrapper" onClick={conversation.status === 'connected' ? stopConversation : startConversation}>
        {/* Outer spinning ring when connected */}
        <div className={`conversation-outer-ring ${conversation.status === 'connected' ? 'connected' : ''}`}>
          {/* Inner button */}
          <div className="conversation-inner-button">
            <button
              className="conversation-action-button"
            >
              {conversation.status === 'connected' ? (
                // Stop icon
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <rect x="6" y="6" width="12" height="12" rx="2" />
                </svg>
              ) : (
                // Microphone icon
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 1c-1.66 0-3 1.34-3 3v8c0 1.66 1.34 3 3 3s3-1.34 3-3V4c0-1.66-1.34-3-3-3z" />
                  <path d="M19 10v2c0 3.87-3.13 7-7 7s-7-3.13-7-7v-2h2v2c0 2.76 2.24 5 5 5s5-2.24 5-5v-2h2z" />
                  <path d="M10 20h4v2h-4z" />
                </svg>
              )}
            </button>
          </div>
        </div>

        {/* Pulsing effect when speaking */}
        {conversation.status === 'connected' && conversation.isSpeaking && (
          <div className="conversation-pulse"></div>
        )}
      </div>

      {/* Call Action Text */}
      <div className="conversation-text-container">
        {/* <p className="conversation-main-text">
          {conversation.status === 'connected' ? 'End call' : 'Try a call'}
        </p> */}
        <p className="conversation-sub-text">
          {conversation.status === 'connected'
            ? (conversation.isSpeaking ? 'Tutor is speaking...' : 'Listening...')
            : 'Start Discussion'}
        </p>
      </div>

      {/* Status indicator */}
      <div className="conversation-status-container">
        <div className={`conversation-status-dot ${conversation.status === 'connected' ? 'connected' : 'disconnected'}`} />
        <span className="conversation-status-text">
          Status: {conversation.status}
        </span>
      </div>
    </div>
  );
}
