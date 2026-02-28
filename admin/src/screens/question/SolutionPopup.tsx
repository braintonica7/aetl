import React, { useEffect } from 'react';
import { Edit, Create, SimpleForm, TextInput, ReferenceInput, SelectInput, required } from 'react-admin';
import * as apiClient from "../../common/apiClient";
import CreateToolbar from "../../common/PopupCreateToolbar";
import 'katex/dist/katex.min.css';
import Latex from 'react-latex-next';
import './style.css';
import { Conversation } from './conversation';

export const SolutionPopup = props => {
    console.log("SolutionPopup props", props);
    const [solution, setSolution] = React.useState("");
    const [loadingSolution, setLoadingSolution] = React.useState(false);
    const [suggestion, setSuggestion] = React.useState("");

    const handleSubmit = (formdata) => {
        props.onClose();
    }

    const fetchSolution = () => {
        setLoadingSolution(true);
        apiClient.fetchSolution(props.record.question_img_url, suggestion)
            .then((res) => {
                console.log("Solution fetched:", res);
                // First check if res has property solution if yes extract that in res
                if (res && res.solution) {
                    res = res.solution;
                }
                setSolution(res);
                apiClient.updateQuestionSolution(
                    props.record.id,
                    res).then(() => {
                        console.log("Solution updated in question record");
                    }).catch((err) => {
                        console.error("Error updating question solution:", err);
                    });
                setLoadingSolution(false);
            }
            )
            .catch((err) => {
                setLoadingSolution(false);
                console.error("Error fetching solution:", err);
            }
            );
    }

    useEffect(() => {
        if (props.record && props.record.question_img_url && props.record.solution === null) {
            fetchSolution();
        } else {
            setSolution(props.record.solution || "");
        }
    }, [props.record]);

    return (
        <div className='solution-popup'>
            <div className='question-image'>
                <div className='question-image-parent'>
                    {props.record && props.record.question_img_url && <img style={{ maxWidth: "100%" }} src={props.record.question_img_url} alt="Question" />}
                </div>
                <div className='question-explanation'>
                    {/* <div className='connect-heading'>Connect to Tutor</div> */}
                    {solution && <Conversation solution={solution}/>}
                </div>
                <div className='close-button'>
                    <input type='button' value='Close' className="theme-button blue size-medium" onClick={handleSubmit} />
                </div>

            </div>
            <div className='solution-section'>
                <div className='solution-text'>
                    {solution && <Latex>
                        {solution}
                    </Latex>
                    }
                </div>
                {loadingSolution && <div className='loader'>Fetching solution...</div>}
                {solution && <div className='solution-fetch-button'>
                    <h3>Fetch Solution</h3>
                    <p>If solution is not correct please add suggestion and refetch solution again.</p>
                    <input name="suggestion" className='suggestion-input' type="text" value={suggestion} onChange={(e) => {
                        setSuggestion(e.target.value);
                    }} />
                    <input type='button' value='Re-Fetch Solution' className="theme-button blue size-medium" onClick={fetchSolution} />

                </div>}
                <div className='solution-explaination'>

                </div>
            </div>
        </div>
    );
}