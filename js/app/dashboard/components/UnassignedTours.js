import React from 'react'
import _ from 'lodash'
import { connect } from 'react-redux'
import { withTranslation } from 'react-i18next'
import { Draggable, Droppable } from "react-beautiful-dnd"

import Tour from './Tour'
import { openCreateTourModal } from '../redux/actions'
import { selectUnassignedTours } from '../../../shared/src/logistics/redux/selectors'


const Buttons = connect(
  () => ({}),
  (dispatch) => ({
    openCreateTourModal: () => dispatch(openCreateTourModal()),
  })
)(({ openCreateTourModal }) => {
  return (
    <React.Fragment>
      <a href="#" className="mr-3" onClick={ e => {
        e.preventDefault()
        openCreateTourModal()
      }}>
        <i className="fa fa-plus"></i>
      </a>
    </React.Fragment>
  )
})


class UnassignedTours extends React.Component {

  render() {
    return (
      <div className="dashboard__panel">
        <h4 className="d-flex justify-content-between">
          <span>{ this.props.t('DASHBOARD_UNASSIGNED_TOURS') }</span>
          <span>
            <Buttons />
          </span>
        </h4>
        <div className="dashboard__panel__scroll">
          <Droppable droppableId="unassigned_tours">
            {(provided) => (
              <div className="list-group nomargin" ref={ provided.innerRef } { ...provided.droppableProps }>
                { _.map(this.props.tours, (tour, index) => {
                  return (
                    <Draggable key={ `tour-${tour['@id']}` } draggableId={ `tour:${tour['@id']}` } index={ index }>
                      {(provided) => (
                        <div
                          ref={ provided.innerRef }
                          { ...provided.draggableProps }
                          { ...provided.dragHandleProps }
                        >
                          <Tour
                            key={ tour['@id'] }
                            tour={ tour }
                            />
                        </div>
                      )}
                    </Draggable>
                  )
                })}
                { provided.placeholder }
              </div>
            )}
          </Droppable>
        </div>
      </div>
    )
  }
}

function mapStateToProps (state) {

  return {
    tours: selectUnassignedTours(state),
  }
}

export default connect(mapStateToProps)(withTranslation()(UnassignedTours))
